/**
 * Seovela AI TinyMCE Plugin
 *
 * Adds an AI button to the TinyMCE toolbar
 *
 * @package Seovela
 */

(function() {
    'use strict';

    tinymce.PluginManager.add('seovela_ai', function(editor, url) {
        
        // Add button to toolbar
        editor.addButton('seovela_ai', {
            title: 'Seovela AI Assistant',
            icon: 'dashicons-superhero-alt',
            image: url.replace('/js/', '/images/') + 'ai-icon.svg',
            onclick: function() {
                // Toggle the AI panel
                var $fab = jQuery('#seovela-ai-fab');
                if ($fab.length) {
                    $fab.trigger('click');
                }
            }
        });

        // Custom icon styling
        editor.on('init', function() {
            var style = document.createElement('style');
            style.textContent = '.mce-i-seovela-ai { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-2-3.5l6-4.5-6-4.5v9z\'/%3E%3C/svg%3E") center/contain; mask: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-2-3.5l6-4.5-6-4.5v9z\'/%3E%3C/svg%3E") center/contain; }';
            document.head.appendChild(style);
        });

    });

})();

