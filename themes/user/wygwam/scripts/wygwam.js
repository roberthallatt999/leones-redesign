function cloneDestroyWygwam(cell) {
    var $textarea = $('textarea', cell),
        $iframe = $('iframe:first', cell);

    // has CKEditor been initialized?
    if (!$iframe.hasClass('wygwam')) {
        // Make a clone of the editor DOM
        $(cell).data('ckeClone', $(cell).children('.cke').clone());

        // save the latest HTML value to the textarea
        var id = $textarea.attr('id'),
            editor = CKEDITOR.instances[id];

        editor.updateElement();

        // destroy the CKEDITOR.editor instance
        editor.destroy();

        // make it look like nothing happened
        $textarea.hide();
        $(cell).data('ckeClone').appendTo(cell);
    }
}

function reinitWygwam(cell) {
    if (typeof $(cell).data('ckeClone') != 'undefined') {
        $(cell).data('ckeClone').remove();
        $(cell).data('ckeClone', null);
    }

    var field_id = $(cell).find('.wygwam textarea').attr('id');
    var config_handle = $('#'+field_id).data('config');
    var defer = $('#'+field_id).data('defer');

    if(defer === 'n') defer = false;

    new Wygwam(field_id, config_handle, defer);
}

function refreshWygwam() {
    $('.wygwam-textarea').each(function() {
        if($(this).parent().hasClass('matrix') === true || $(this).parent().hasClass('wygwam') === true || $(this).parent().data('fieldtype') == 'wygwam') {
            var field_id = ($(this).attr('id')).replace('cke_', '');
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');
            var editor = CKEDITOR.instances[field_id];

            if(defer === 'n') {
                defer = false;
            } else {
                // if (editor !== undefined) {
                //     editor.destroy();
                // }

                if ($(this).siblings('iframe')) {
                    $(this).siblings('iframe').remove();
                }
            }

            new Wygwam(field_id, config_handle, defer);
        }
    });

    // Bloqs Live Preview Re-init
    $('[data-fieldtype="wygwam"]').each(function() {
        var field_id = $(this).find('textarea').attr('id');

        if (field_id) {
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');
            var editor = CKEDITOR.instances[field_id];

            if(defer === 'n') {
                defer = false;
            } else {
                // if (editor !== undefined) {
                //     editor.destroy();
                // }

                if ($(this).find('textarea').siblings('iframe')) {
                    $(this).find('textarea').siblings('iframe').remove();
                }
            }

            new Wygwam(field_id, config_handle, defer);
        }
    });
}

window.Wygwam;

(function($) {

    /**
     * Wygwam
     */
    Wygwam = function(id, config, defer) {
        // Allow initializing by a jQuery object that matched something
        if (typeof id == "object" && typeof id.is == "function" && id.is('textarea')) {
            this.$element = id;
        } else {
            this.$element = $('#' + id);
        }

        // No luck
        if (this.$element.length == 0) {
            return;
        }

        this.id = id;

        if (typeof config == "undefined") {
            config = this.$element.data('config');
        }

        this.config = (Wygwam.configs[config] || Wygwam.configs['default']);

        if (typeof defer == "undefined") {
            this.defer = this.$element.data('defer') == "y";
        } else {
            this.defer = defer;
        }

        if (this.defer) {
            this.showIframe();
        } else {
            this.initCKEditor();
        }
    };

    Wygwam.prototype = {
        /**
         * Show Iframe
         */
        showIframe: function() {
            var width = (this.config.width ? this.config.width.toString() : '100%'),
                height = (this.config.height ? this.config.height.toString() : '200'),
                css = (this.config.contentsCss ? this.config.contentsCss : Wygwam.themeUrl+'lib/ckeditor/contents.css'),
                $textarea = this.$element.hide();

            if (width.match(/\d$/)) width += 'px';
            if (height.match(/\d$/)) height += 'px';

            this.$iframe = $('<iframe class="wygwam" style="width:'+width+'; height:'+height+';" frameborder="0" />').insertAfter($textarea);

            var iDoc = this.$iframe[0].contentWindow.document,
                html = '<html>'
                     +   '<head>'
                     +     '<link rel="stylesheet" type="text/css" href="'+css+'" />'
                     +     '<style type="text/css">* { cursor: pointer !important; }</style>'
                     +   '</head>'
                     +   '<body>'
                     +     $textarea.val()
                     +   '</body>'
                     + '</html>';

            iDoc.open();
            iDoc.write(html);
            iDoc.close();

            $(iDoc).click($.proxy(this, 'initCKEditor'));
        },

        /**
         * Init CKEditor
         */
        initCKEditor: function() {
            // Load in any custom config values
            if (CKEDITOR.editorConfig) {
                CKEDITOR.editorConfig(this.config);
            }

            var editor = CKEDITOR.instances[this.id];
            if (editor) {
                editor.destroy(true);
            }

            if (this.$iframe) {
                this.$iframe.remove();
            }

            CKEDITOR.replace(this.id, this.config); //this.$element[0]
        }
    }


    function makeDelay(ms) {
        var timer = 0;
        return function(callback){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    };

    // When CKEditor is updated, update the textarea so EE will trigger it's auto-save or remove error messages.

	var delay = makeDelay(700);
    CKEDITOR.on('instanceCreated', function(e) {
		e.editor.on('change', function (event) {
            delay(function() {
                // Update the textarea's content.
                this.updateElement();
                // Trigger the `onchange` handlers for the textarea so EE will catch the update.
                $(this.element.$).change();
                // We have to focus and blur the real textarea otherwise EE5 won't pick up the change.
                $('#' + this.element.$.id).focus().blur();
           }.bind(this));
		});
    });

    $('body').on('keyup', '.wygwam .cke_source', function(){
        var textareaVal = $(this).val();
        var title = this.title;
        var offset = title.indexOf('field_id_');
        let result = title.substring(offset, );
        $('#' + result).val(textareaVal);
        $('#' + result).text(textareaVal);
        $('#' + result).change();
    });

    if (typeof FluidField !== 'undefined') {
        FluidField.on('wygwam', 'add', function(row) {
            var field_id = row.find('.wygwam textarea').attr('id');
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');

            if(defer === 'n') defer = false;

            new Wygwam(field_id, config_handle, defer);
        });

        /**
         * Before Sort
         */
        FluidField.on('wygwam', 'beforeSort', function(cell){
            cloneDestroyWygwam(cell);
        });

        /**
         * After Sort
         */
        FluidField.on('wygwam', 'afterSort', function(cell) {
            reinitWygwam(cell);
        });
    }

    $(document).on('entry:preview-open', refreshWygwam);
    $(document).on('entry:preview-close', refreshWygwam);

    Wygwam.configs = {};
    Wygwam.assetIds = [];

    /**
     * Load Assets Sheet
     */
    Wygwam.loadAssetsSheet = function(params, filedir, kind) {
        var sheet = new Assets.Sheet({
            filedirs: (filedir == 'all' ? filedir : [filedir]),
            kinds: (kind == 'any' ? kind : [kind]),

            onSelect: function(files) {
                var cacheBustUrl = files[0].url + '?cachebuster:' + parseInt(Math.random() * 100, 10);
                CKEDITOR.tools.callFunction(params.CKEditorFuncNum, cacheBustUrl);

                if (files[0].id) {
                    if ($.inArray(files[0].id, Wygwam.assetIds) == -1) {
                        for (var instanceName in CKEDITOR.instances) break;
                        var ckeditorContainerElem = CKEDITOR.instances[instanceName].container.$;
                        var $form = $(ckeditorContainerElem).closest('form');

                        $('<input type="hidden" name="wygwam_asset_ids[]"/>').val(files[0].id).appendTo($form);
                        $('<input type="hidden" name="wygwam_asset_urls[]"/>').val(files[0].url).appendTo($form);

                        Wygwam.assetIds.push(files[0].id);
                    }
                }
            }
        });

        sheet.show();
    };


    /**
     * Load EE File Browser
     */
    Wygwam.loadEEFileBrowser = function(params, directory, content_type, dirURL) {
        // Set up the temporary increase of z-indexes.
        var modalZIndex = $('.modal-file').css('z-index'),
            overlayZindex = $('.overlay').css('z-index'),
            appOverlayZindex = $('.app-overlay').css('z-index');

        $('.modal-file').css('z-index', 10012);
        $('.overlay, .app-overlay').css('z-index', 10011);

        if ($('html').css('position') === 'fixed') {
            $('body').css({ position:'fixed', width:'initial' });
        }

        $('.cke_dialog_open').css({'overflow': 'scroll'})

        // Set up restoration of original z-indexes.
        var restoreZIndexes = function (){
            $('.cke_dialog_open').css({'overflow': 'hidden'})
            $('.modal-file').css({'z-index': modalZIndex});
            $('.overlay').css({'z-index': overlayZindex});
            $('.app-overlay').css({'z-index': appOverlayZindex});
            $('body').css({overflow:'', position:'initial', width:'initial' });
        };

        var $trigger = $('<trigger class="m-link filepicker" rel="modal-file" href="' + dirURL + '"/>').appendTo('body');

        $trigger.FilePicker({
            callback: function(data, references)
            {
                //var url = Wygwam.filedirUrls[data.upload_location_id] + data.file_name;
                // ee7 : the old way above is not working for subfolders because subfolders are not considered to be "upload locations" that are passed to the js earlier.
                var url = data.path;

                CKEDITOR.tools.callFunction(params.CKEditorFuncNum, url);
                references.modal.find('.m-close').click();
                $('body').off('modal:close', '.modal-file', restoreZIndexes);
            }
        });

        $trigger.click();

        // Set up the listener to restore the z-indexes.
        $('body').on('modal:close', '.modal-file', restoreZIndexes);
    };

    // If we are in the front end such as a wygwam field in a channels form

    Wygwam.loadEEFileBrowserFront = function(params, directory, content_type, dirURL) {
        const collection = document.getElementsByClassName("cke_dialog_ui_hbox_first")[0];
        var parser = new DOMParser();
        var htmlDoc = parser.parseFromString(dirURL, 'text/html');
        collection.appendChild(htmlDoc.body);
        var select = document.getElementById('unique_file_id');


        var img = document.createElement('img');
        img.src = select.options[select.selectedIndex].value;
        img.style = "width:150px;height:150px;";
        collection.appendChild(img);

        select.addEventListener("change", function() {
            var value = select.options[select.selectedIndex].value;
            img.src = select.options[select.selectedIndex].value;
            img.style = "width:150px;height:150px;";
            var url_bar = document.getElementsByClassName("cke_dialog_ui_input_text")[0];
            const inputElements = url_bar.querySelectorAll("input, select, checkbox, textarea")[0];
            inputElements.value = value;


        });
    };
})(jQuery);
