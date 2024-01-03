tinymce.PluginManager.add('wpmedia', function(editor, url) {
    // Add a button that opens a window
    editor.addButton('wpmedia', {
        title: 'WP Image',
        icon: 'wp-media-library',
        onclick: function() {           
            if ( wp && wp.media && wp.media.editor ) {
			wp.media.editor.open( editor.id );
            }
        }
    });

   
});