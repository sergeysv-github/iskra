$(function(){	
	// Initialize WYSIWYG editors.
	if (jQuery().wysiwyg) {
		var i = 1;
		$('textarea.wysiwyg-editor').each(function()
		{
			var $textarea = $(this);
			
			// Hide the textarea and prepare the editor DIVs instead.
			$textarea.hide();
		
			var editor_id = 'wysiwyg-editor-'+ i;
			$textarea.attr('data-relates-to', editor_id);
			
			var height = 0;
			if (typeof $textarea.attr('data-height') !== 'undefined') {
				height = $textarea.attr('data-height');
			}
			
			// Build all required HTML.
			var editor = '<div class="wysiwyg-toolbar clearfix">';
			editor += '<div class="btn-toolbar pull-left" data-role="editor-toolbar" data-target="#'+ editor_id +'" id="'+ editor_id +'-toolbar" role="toolbar"></div>';
			editor += '<div class="pull-right wysiwyg-toolbar-additional"></div>';
			editor += '</div>';
			editor += '<div id="'+ editor_id +'" class="form-control wysiwyg-editor"';
			if (height > 0) {
				editor += ' style="height: '+ height +'px"';
			}
			editor += '></div>';
			
			// Create editor's DOM.
			$(editor).insertAfter($textarea);
			var $editor = $('div[id="'+ editor_id +'"]');
			
			// Populate the toolbar.
			var $toolbar = $('#'+ editor_id +'-toolbar');
			var buttons;
			
			// Undo/Redo.
			buttons  = '<a class="btn btn-default btn-sm" data-edit="undo" title="Undo (Ctrl/Cmd+Z)"><i class="fa fa-undo"></i></a>';
			buttons += '<a class="btn btn-default btn-sm" data-edit="redo" title="Redo (Ctrl/Cmd+Y)"><i class="fa fa-repeat"></i></a>';
			$('<div class="btn-group" role="group">'+ buttons +'</div>').appendTo($toolbar);
			
			// Styles.
			buttons  = '<a class="btn btn-default btn-sm" data-edit="bold" title="Bold (Ctrl/Cmd+B)"><i class="fa fa-bold"></i></a>';
			buttons += '<a class="btn btn-default btn-sm" data-edit="italic" title="Italic (Ctrl/Cmd+I)"><i class="fa fa-italic"></i></a>';
			buttons += '<a class="btn btn-default btn-sm" data-edit="strikethrough" title="Strikethrough"><i class="fa fa-strikethrough"></i></a>';
			buttons += '<a class="btn btn-default btn-sm" data-edit="underline" title="Underline (Ctrl/Cmd+U)"><i class="fa fa-underline"></i></a>';
			$('<div class="btn-group" role="group">'+ buttons +'</div>').appendTo($toolbar);
			
			// Lists.
			buttons  = '<a class="btn btn-default btn-sm" data-edit="insertunorderedlist" title="Bullet list"><i class="fa fa-list-ul"></i></a>';
			buttons += '<a class="btn btn-default btn-sm" data-edit="insertorderedlist" title="Number list"><i class="fa fa-list-ol"></i></a>';
			$('<div class="btn-group" role="group">'+ buttons +'</div>').appendTo($toolbar);
			
			// Links.
			buttons  = '<a class="btn btn-default btn-sm btn-dropdown-toggle" data-toggle="dropdown" title="Hyperlink"><i class="fa fa-link"></i></a>';
			buttons += '<div class="dropdown-menu input-append">';
			buttons += '<div class="input-group">';
			buttons += '<input class="form-control input-sm" placeholder="URL" type="text" data-edit="createLink">';
			buttons += '<span class="input-group-btn"><button class="btn btn-default btn-sm" type="button">Add</button></span>';
			buttons += '</div>';
			buttons += '</div>';
			buttons += '<a class="btn btn-default btn-sm" data-edit="unlink" title="Remove Hyperlink"><i class="fa fa-unlink"></i></a>';
			$('<div class="btn-group" role="group">'+ buttons +'</div>').appendTo($toolbar);
			
			// Init editor.
			$editor.wysiwyg().html($textarea.val());
			
			// Make the original textarea track all changes (we'll need this for submit).
			$('#'+editor_id).keyup(function(){
				$textarea.html($editor.cleanHtml());
			});
			
			i++;
		});
	}
});