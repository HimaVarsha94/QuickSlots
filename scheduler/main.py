#!/usr/bin/env python

from munkres import Munkres, print_matrix, make_cost_matrix
import pymysql
import json
import itertools
import sys
import time
import os

MAX_PREFERENCE_COUNT = 3

def schedule():
    # connect to the database
    host = os.getenv("DB_HOST", "127.0.0.1")
    port = 3306
    user = "root"
    passwd = "abc123"
    database = "quickslots"
    conn = pymysql.connect(host=host, port=port, user=user, passwd=passwd, db=database, cursorclass=pymysql.cursors.DictCursor)

    # time mapping
    slot_mapping = {
        '0900-0955': [1],
        '1000-1055': [2],
        '1100-1155': [3],
        '1200-1255': [4],
        '1300-1425': [5],
        '1430-1555': [6],
        '1600-1725': [7],
        '1730-1900': [8],
        '1900-2030': [9],
        '1430-1725': [6,7],
        '0900-1155': [1,2,3]
    }


    # extract batches
    query = "SELECT * FROM allowed"
    batches = None
    with conn.cursor() as cursor:
        cursor.execute(query)
        batches = cursor.fetchall()

    # extract all the courses
    query = "SELECT * from courses"
    result = None
    with conn.cursor() as cursor:
        cursor.execute(query)
        result = cursor.fetchall()

    # form the dictionary for courses
    course_dict = {}
    for course in result:
        course_dict[course['course_id']] = {
            'type': course['type'],
            'rCount': course['registered_count'],
            'allowConflict': int(course['allow_conflict']),
            'preferences': [],
            'allowed': [],
            'edge_weights': []}

    # extract all the preferences
    query = "SELECT * from preferences"
    result = None
    with conn.cursor() as cursor:
        cursor.execute(query)
        result = cursor.fetchall()
    # for the preferences for courses
    for pref in result:
        course_dict[pref['course_id']]['preferences'].append(pref['slot_group'])


    # extract allowed batches
    query = "SELECT * FROM allowed"
    result = None
    with conn.cursor() as cursor:
        cursor.execute(query)
        result = cursor.fetchall()
    # for the preferences for courses
    for allow in result:
        course_dict[allow['course_id']]['allowed'].append(allow['batch_name'])

    # extract all the room groups
    query = "SELECT * from rooms"
    result = None
    with conn.cursor() as cursor:
        cursor.execute(query)
        result = cursor.fetchall()
    # form the rooms dictionary
    rooms_dict = {}
    for room in result:
        rooms_dict[room['room_name']] = {
            'capacity': room['capacity'],
            'lab': int(room['lab'])}

    # extract all slot groups
    query = "SELECT * from slot_groups"
    with conn.cursor() as cursor:
        cursor.execute(query)
        result = cursor.fetchall()
    # form the slots dictionary
    slots_dict = {}
    for slot in result:
        slots_dict[slot['id']] = {
            'tod': slot['tod'],
            'lab': int(slot['lab']),
            'slots': json.loads(slot['slots'])}

    # define the right independent set for the graph
    right_nodes = [p for p in itertools.product(rooms_dict.keys(), slots_dict.keys())]
    def map_correct_nodes(node):
        room_id = node[0]
        slot_group_id = node[1]
        return rooms_dict[room_id]['lab'] == slots_dict[slot_group_id]['lab']
    # only match lab rooms with lab slot_groups
    right_nodes = filter(map_correct_nodes, right_nodes)

    # define the edges for the graph
    N = len(course_dict.keys())
    B = len(batches)
    for course in course_dict:
        P = len(course_dict[course]['preferences'])
        weights = []
        for i in xrange(len(right_nodes)):
            node = right_nodes[i]
            room_id = node[0]
            slot_group_id = node[1]
            if course_dict[course]['type'] == 'lab' and rooms_dict[room_id]['lab'] != 1:
                weights.append(sys.maxint)
                continue
            elif course_dict[course]['type'] != 'lab' and rooms_dict[room_id]['lab'] == 1:
                weights.append(sys.maxint)
                continue
            elif course_dict[course]['rCount']-rooms_dict[room_id]['capacity'] > 20:
                weights.append(sys.maxint)
                continue

            # weights for course preferences
            pweight = 0
            try:
                j = course_dict[course]['preferences'].index(slot_group_id)
                pweight = pow(P,j)
            except ValueError as e:
                pweight = 2*pow(MAX_PREFERENCE_COUNT, MAX_PREFERENCE_COUNT)

            # defining weights
            k = len(course_dict[course]['allowed'])
            # weights for allowed batches
            aweight = 1-float(k)/(B+1)
            # weights for type of course
            tweight = float(1)/N
            if course_dict[course]['allowConflict'] == 0:
                tweight /= 5
            elif course_dict[course]['type'] == 'lab':
                tweight /= 25
            # weight for room deficit
            rweight = float(1+abs(course_dict[course]['rCount']-rooms_dict[room_id]['capacity']))/rooms_dict[room_id]['capacity']
            # assign the weight for the edge
            weights.append(aweight*pweight*tweight*rweight)
        course_dict[course]['edge_weights'] = weights


    # define the graph
    graph = []
    for course in course_dict:
        graph.append(course_dict[course]['edge_weights'])

    timetable_name = time.strftime("%d-%m-%Y-%H-%M-%S")
    query = "INSERT INTO timetables VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE `table_name` = VALUES(`table_name`)"
    with conn.cursor() as cursor:
        cursor.execute(query, [timetable_name, "5","9","90","09","00","AM","0","0","0"])
        conn.commit()

    # compute and store assignment for allocations
    m = Munkres()
    indexes = m.compute(graph)
    for row, column in indexes:
        # value = graph[row][column]
        # print course_dict.keys()[row], right_nodes[column]
        slot_group_temp = right_nodes[column][1]
        room_temp = right_nodes[column][0]
        course_temp = course_dict.keys()[row]
        slots = slots_dict[slot_group_temp]['slots']
        for s in slots:
            slot_num_list = slot_mapping[s[1]+"-"+s[2]]
            for slot_num in slot_num_list:
                query = "INSERT INTO slots(`table_name`, `day`, `slot_num`, `state`) values (%s,%s,%s,%s)"
                with conn.cursor() as cursor:
                    cursor.execute(query, [timetable_name, s[0], slot_num, "active"])
                    conn.commit()
                query = "INSERT INTO slot_allocs(`table_name`, `day`, `slot_num`, `room`, `course_id`) values (%s,%s,%s,%s,%s)"
                with conn.cursor() as cursor:
                    cursor.execute(query, [timetable_name, s[0], slot_num, room_temp, course_temp])
                    conn.commit()

    print timetable_name

if __name__ == "__main__":
    schedule()
