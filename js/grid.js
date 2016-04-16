/**
 * Client side script to render timetable grids
 * Avin EM; Kunal Dahiya
 */
var colors=
[
    ['rgba(65, 127, 242, 0.18)','rgba(58, 56, 207, 0.41)'],

    ['rgba(200, 0, 166, 0.18)', 'rgba(137, 0, 152, 0.38)'],

    ['rgba(198, 250, 250, 0.75)', 'rgba(0, 95, 96, 0.48)'],

    ['rgba(159, 255, 104, 0.5)', 'rgba(54, 126, 18, 0.60)'],

    ['#fefa9a', '#C99B08']
]
/*Hard coded
To DO : Automate This*/
function other(i){
	i = parseInt(i)
	if(i==0){
		return "10:00 AM";
	} else if(i==1){
		return "11:00 AM";
	}else if(i==2){
		return "12:00 PM";
	}else if(i==3){
		return "01:00 PM";
	}else if(i==4){
		return "02:30 PM";
	}else if(i==5){
		return "04:00 PM";
	}else if(i==6){
		return "05:30 PM";
	}else if(i==7){
		return "07:00 PM";
	}else if(i==8){
	return "08:30 PM";
	}
}


function timeAdd(t,dur)
{
    var hr = parseInt(t.substr(0,2)),
        min = parseInt(t.substr(3,2)),
        mer = t.substr(-2),
        h = parseInt(dur/60);
    min += dur - h*60;
    hr +=h;
    h = parseInt(min/60);
    hr +=h;
    min -= h*60;
    if(hr>=12 && (h || dur>=60) )
    {
        hr = (hr + 11) % 12 + 1;
        mer=(mer=="PM")?"AM":"PM";
    }  
    return ("0"+hr).substr(-2)+":"+("0"+min).substr(-2)+" "+mer;
}

function drawGrid(not_empty,slots,numDays,dur,t)
{
    var cell_color="";
    if (!slots)
    {
      slots = parseInt($("#numSlots").val()),
          numDays  = parseInt($("#numDays").val()),
          dur   = parseInt($("#duration").val()),
          t     = $("#start_hr").val() + ":" + $("#start_min").val()
                  + " " + $("#start_mer").val();
      cell_color = "blue";
    }
    var table = $("#timetable"),
        row=$("<div>").addClass('row');
        row.append($("<div>").addClass('cell blank'));
    if(!slots || !numDays)
    {
        $("#legend").hide();
        $("#updateButton").hide();
        msg = 'There are no timetables to display';
        if(not_empty)
          msg ='Add one or more slots and days to display the timetable'
        table.html('<br><br><div style="font-weight:bold;text-align:center">' + msg + '</div>');
        table.css('height','100px');
        return;
    }
    else
    {
        table.removeAttr('style');
        $("#updateButton").show();
        $("#legend").show();
    }
    table.html('');
    for(i=0;i<slots;i++)
    {
        var content = t+'<br>−<br>';
        //t=timeAdd(t,dur);
        t = other(i);
		content+=t;
        row.append($("<div>").addClass('cell time').html(content));            
    }
    table.append(row);
    var days = ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
    for(d=0;d<numDays;d++) 
    {
        row = $("<div>").addClass('row');
        row.append($("<div>").addClass('cell day').html(days[d]));
        for (i=0; i < slots; i++)
            row.append($('<div id="'+ (d+1)+"_"+(i+1) +'">').addClass("cell "+cell_color)); 
        table.append(row);
    }
    $("#disabledSlots input[value=disabled]").each(function(){
        var cell=$("#"+this.name);
        if(cell[0])
            $(cell).removeClass('blue').addClass('disabled');
        else
            $(this).remove();
    })
}
function colorCourses()
{
    $(".course").each(function(i){
        $(this).css('background',colors[i%colors.length][0]);
    })
}
