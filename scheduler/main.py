from munkres import Munkres, print_matrix, make_cost_matrix
import pymysql
import json
import itertools
import sys

MAX_PREFERENCE_COUNT = 3

def schedule():
    # connect to the database
    host = "127.0.0.1"
    port = 3306
    user = "root"
    passwd = "abc123"
    database = "quickslots"
    conn = pymysql.connect(host=host, port=port, user=user, passwd=passwd, db=database, cursorclass=pymysql.cursors.DictCursor)

    # select * from courses where fac_id='subruk';
    # select * from rooms join slot_groups;

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
            'lab': int(slot['lab'])}

    conn.close()

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

    # cost_matrix = make_cost_matrix(graph, lambda cost: sys.maxint - cost)
    m = Munkres()
    indexes = m.compute(graph)
    total = 0
    for row, column in indexes:
        value = graph[row][column]
        total += value
        # print '(%d, %d) -> %f' % (row, column, value)
        print course_dict.keys()[row], right_nodes[column], value
    # print 'total profit=%d' % total

if __name__ == "__main__":
    schedule()
