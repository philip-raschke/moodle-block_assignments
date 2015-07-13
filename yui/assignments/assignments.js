YUI.add('moodle-block_assignments-assignments', function(Y) {
  
	M.block_assignments = M.block_assignments || {};
	var NS = M.block_assignments.assignments = {};
 
	NS.init = function() {
		
		var showBtns = document.getElementsByClassName('btn-show-all');
		var flags = [];
		var ids = [];
		
		for(var i=0; i < showBtns.length; i++) {
			flags[i] = false;
			ids[i] = showBtns[i].parentNode.parentNode.parentNode.parentNode.id;
			
			showBtns[i].href = 'javascript:';
			showBtns[i].onclick = function(i, e) {
				
				if(!flags[i]) {
					showBtns[i].innerHTML = '<b>' + M.util.get_string('hide', 'block_assignments') + '</b>';
					flags[i] = true;
					
					var rows = document.querySelectorAll('#' + ids[i] + ' tr');
					for(var j=0; j < rows.length; j++)
						rows[j].style.display = '';
				}
				else {
					showBtns[i].innerHTML = '<b>' + M.util.get_string('more', 'block_assignments') + '</b>';
					flags[i] = false;
					
					var rows = document.querySelectorAll('#' + ids[i] + ' tr');
					for(var j=0; j < rows.length-1; j++) {
						if(j <= 5)
							continue;
						
						rows[j].style.display = 'none';
					}
				}
			}.bind(null, i);
		}
		
	};
}, '@VERSION@', {
  requires: ['node']
});