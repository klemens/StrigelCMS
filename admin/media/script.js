window.onload = function() {
	
	var sort = document.getElementById('page_content_type');
	
	if(sort) {
        function updateSelected() {
            for (var i=0;i<sort.length;i++) {
                if (sort.selectedIndex == i)
                    document.getElementById('page_content_type'+i).style.display = 'block';
                else
                    document.getElementById('page_content_type'+i).style.display = 'none';
            }
        }
        
        updateSelected();
        
        sort.onchange = function() {
            updateSelected();
        }
    }
    
    
    
    var advSet = document.getElementById('page_adv_set-1');
    
    if(advSet) {
        function showAdvSet() {
            if(advSet.checked)
                document.getElementById('page_adv_set').style.display = 'block';
            else
                document.getElementById('page_adv_set').style.display = 'none';
        }
        
        showAdvSet();
        
        advSet.onclick = function() {
            showAdvSet();
        }
    }
    
    var editSet = document.getElementById('page_editor_set-1');
    
    if(editSet) {
        function showEditSet() {
            if(editSet.checked) {
                if(page_html_link_replace_do) {
                    var page_html_text = document.getElementById('page_html').value;
                    page_html_text = page_html_text.replace(new RegExp(page_html_link_search, "gi"), page_html_link_replace);
                    document.getElementById('page_html').value = page_html_text;
                }
                tinyMCE.execCommand('mceAddControl', false, 'page_html');
            } else {
                tinyMCE.execCommand('mceRemoveControl', false, 'page_html');
                if(page_html_link_replace_do) {
                    var page_html_text = document.getElementById('page_html').value;
                    page_html_text = page_html_text.replace(new RegExp(page_html_link_replace, "gi"), page_html_link_search);
                    document.getElementById('page_html').value = page_html_text;
                }
            }
        }
        
        showEditSet();
        
        editSet.onclick = function() {
            showEditSet();
        }
    }
    
    if(document.forms[1]) {
        document.forms[1].onsubmit = function() {
            if(document.getElementById('page_editor_set-1').checked) {
                tinyMCE.execCommand('mceRemoveControl', false, 'page_html');
                if(page_html_link_replace_do) {
                    var page_html_text = document.getElementById('page_html').value;
                    page_html_text = page_html_text.replace(new RegExp(page_html_link_replace, "gi"), page_html_link_search);
                    document.getElementById('page_html').value = page_html_text;
                }
            }
            return true;
        }
    }
}