$(document).ready(function() { 
	$('#contacts_tbl').tablesorter({
		sortList: [[0,0]],
		headers: {  
			3: { 
		   	 sorter: false 
			}, 
		} 
	}); 
	
	$('#tasks_tbl').tablesorter({
		sortList: [[4,1]],
		headers: {  
			2: { 
				sorter: false 
			}, 
		} 
	});
	
	$('#history_tbl').tablesorter({
		sortList: [[2,1]],
	});
}); 
