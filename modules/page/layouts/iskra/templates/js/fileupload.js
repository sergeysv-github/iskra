$(function(){
	function randomString() {
		var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
		var string_length = 7;
		var randomstring = '';
		for (var i=0; i<string_length; i++) {
			var rnum = Math.floor(Math.random() * chars.length);
			randomstring += chars.substring(rnum,rnum+1);
		}
		return randomstring;
	}
	
	// Create a random token to be used as a temporary file storage.
	// Also add a progress bar indicator.
	$('.file-uploader').closest('form')
		.append('<input type="hidden" name="tmp_dir" value="'+ randomString() +'">')
		.append('<div id="files-progress" class="progress hidden"><div class="progress-bar progress-bar-striped progress-bar-success"></div></div>')
		.append('<div id="files-list" class="files"></div>');
	
    $('.file-uploader').fileupload({
        url: '/index.php?q=files/upload',
        dataType: 'json',
        done: function (e, data) {
            $.each(data.result.files, function (index, file) {
                var button = '<a class="btn btn-danger btn-xs file-delete-button" href="'+ file.deleteUrl +'">Delete</a>';
                var input = '<input type="hidden" name="filenames[]" value="'+ file.name +'">';
                $('<p class="filename">'+ button +' '+ file.name + input +'</p>')
                    .appendTo('#files-list');
                $('.file-delete-button').click(function(e){
                    var tmp_dir = $(e.target).closest('form').find('input[name=tmp_dir]').val();
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('href'),
                        type: 'POST',
                        dataType: 'json',
                        data: {tmp_dir: tmp_dir},
                        success: function() {
                            $(e.target).closest('p.filename').remove();
                        }
                    });
                });
            });
            $('#files-progress').addClass('hidden');
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#files-progress').removeClass('hidden');
            $('#files-progress .progress-bar').css(
                'width',
                progress + '%'
            );
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
});