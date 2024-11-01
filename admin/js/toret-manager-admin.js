(function ($) {
    'use strict';


    $(document).ready(function () {

        /*
        * To the top button
         */
        let mybutton = document.getElementById("trman-to-top-button");

        window.onscroll = function () {
            scrollFunction()
        };

        function scrollFunction() {

            if (mybutton)
                if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                    mybutton.style.display = "block";
                } else {
                    mybutton.style.display = "none";
                }

        }

        jQuery(document).on("click", '#trman-to-top-button', function (e) {

            document.body.scrollTop = 0; // For Safari
            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera

        });


        jQuery(document).on("click", '.trman-module-anchor', function (e) {

            e.preventDefault()
            let targetElement = $('.' + $(this).data('target'))
            if (targetElement.length) {
                $([document.documentElement, document.body]).animate({
                    scrollTop: targetElement.offset().top
                }, 1000);
            }


        });

        jQuery(document).on("click", '.trman-sync-button', function (e) {

            jQuery(this).prop('disabled', true);
            jQuery(this).closest('form').trigger("submit");

        });


        /**
         * Save internal id from metabox
         */
        jQuery(document).on("click", '.trman-save-internalid', function (e) {

            e.preventDefault()

            let item_id = jQuery(this).data('id')
            let type = jQuery(this).data('type')
            let internal_id = jQuery('#toret_manager_item_internal_id_' + item_id).val()

            let data = {
                action: 'trman_save_product_internalid',
                item_id: item_id,
                internal_id: internal_id,
                type: type,
                nonce: trman_admin_localize.nonce
            };
            $.post(ajaxurl, data, function (response) {
                jQuery('#trman-save-internalid-' + item_id).prop('disabled', true);
            });

        });

        jQuery(document).on("keyup", '.trman-internalid-form-field', function (e) {
            let item_id = jQuery(this).data('id')
            jQuery('#trman-save-internalid-' + item_id).prop('disabled', false);
        });

        /**
         * Check all
         */
        $(".trman-check-all").each(function (a) {
            let optionGroup = $(this).data('option')
            if ($('.' + optionGroup).length == $('.' + optionGroup).length) {
                $(this).prop('checked', true);
            }
        });

        jQuery(document).on("change", '.trman-check-all', function (e) {

            let option = $(this).data('option')
            let checked = $(this).is(':checked')

            $("." + option).each(function (a) {
                if (!$(this).prop('readonly')) {
                    if (checked) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                    }
                }
            });

        });

        function checkAll(element) {
            let option = element.data('option')
            $("." + option).each(function (a) {
                if (!element.prop('readonly')) {
                    $(this).prop('checked', true);
                }
            });
        }


        var ajaxEnabled = true;

        jQuery(document).on("click", '.trman-toggle-slider', function (e) {

            if (ajaxEnabled) {

                let target = $(this).data('target')
                let option = $(this).data('option')
                let module = $(this).data('module')
                let endpoint = $(this).data('endpoint')

                if ($('#' + option).is(':checked')) {
                    $('.' + target).hide();
                    $('.link-' + module).removeClass('active-module')
                    save_module_state(module, 'disabled', endpoint)
                    ajaxEnabled = false
                } else {
                    $('.' + target).show();
                    $('.link-' + module).addClass('active-module')
                    save_module_state(module, 'enabled', endpoint)
                    ajaxEnabled = false
                }
            } else {
                e.preventDefault()
            }
        });


        function save_module_state(module, state, endpoint) {
            let data = {
                action: 'trman_save_module_state',
                module: module,
                state: state,
                endpoint: endpoint,
                nonce: trman_admin_localize.nonce
            };

            $.post(ajaxurl, data, function (response) {
                ajaxEnabled = true
            });
        }


        /**
         *
         */
        jQuery(document).on("change", '#trman-log-filter', function (e) {
            jQuery(this).parent().submit()
        });

        jQuery(document).on("change", '.trman-prop-main-checbox-all', function (e) {
            if ($(this).is(':checked')) {
                checkAll($('#' + $(this).data('target') + '-checkall'))
                $('#' + $(this).data('target') + '-checkall').prop('checked', true);
                $('#' + $(this).data('target')).hide()
                if ($(this).next().next().prop('checked', true)) {
                    $(this).next().next().prop('checked', false)
                }
            }
        });

        jQuery(document).on("change", '.trman-prop-main-checbox', function (e) {
            if ($(this).is(':checked')) {
                $('#' + $(this).data('target')).show()
                if ($(this).prev().prev().prop('checked', true)) {
                    $(this).prev().prev().prop('checked', false)
                }
            } else {
                $('#' + $(this).data('target')).hide()
            }
        });

    });


    /**
     * Toret admin page
     */
    jQuery('document').ready(function () {
        let toret_copy = document.getElementById("toret-copy");

        if (toret_copy)
            toret_copy.addEventListener("click", function () {
                var t = document.getElementById("toret-plugins-admin-diag").textContent;
                var fn = "ToretDiagnostika.txt";
                dwn(fn, t);
            }, false);

        function dwn(fn, t) {
            var element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(t));
            element.setAttribute('download', fn);
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        }
    });


    /**
     * Overlay on ajax save
     */
    function createOverlay(element) {
        var overlayHTML = '<div id="trmanLoadingBar"><div id="trmanLoadingSpinner"></div></div>';
        $('#trmanLoadingBar').remove();
        element.closest('.trman-admin-container-body-wrap').append(overlayHTML);
    }

    function showLoadingOverlay() {
        $('#trmanLoadingBar').show();
    }

    function hideLoadingOverlay() {
        $('#trmanLoadingBar').hide();
    }

    /**
     * Save option
     */
    jQuery('document').ready(function () {
        var disableSave = false;

        jQuery(document).on("click", '.trman-option', function (e) {
            if(disableSave) {
                e.preventDefault()
                return false;
            }
        });

        jQuery(document).on("change", '.trman-option', function (e) {

            createOverlay($(this));
            showLoadingOverlay();

            disableSave = true;

            let scope = "";
            let disabled = "";

            if($(this).hasClass('trman-option-all')){
                scope = 'all';
                if(!$(this).is(':checked') && !$(this).next().is(':checked')) {
                    disabled = true;
                }
            }else if($(this).hasClass('trman-option-partial')) {
                if(!$(this).is(':checked') && !$(this).prev().is(':checked')) {
                    disabled = true;
                }
                scope = 'part';
            }
            let data = {
                action: 'trman_save_option',
                option: $(this).attr('name'),
                value: $(this).val(),
                checked: $(this).is(':checked'),
                type: $(this).attr('type'),
                nonce: trman_admin_localize.nonce,
                scope: scope,
                disabled: disabled
            };

            $.ajax({
                url: ajaxurl,
                data: data,
                method: 'POST',
                success: function(response) {
                    disableSave = false;
                    hideLoadingOverlay();
                },
                error: function() {
                    hideLoadingOverlay();
                }
            });

        });

        /**
         * Save option - items
         */
        jQuery(document).on("change", '.trman-option-items', function (e) {
            if($(this).is('[readonly]')){
                 return false;
            }

            createOverlay($(this));
            showLoadingOverlay();

            let name = ""
            if($(this).hasClass('trman-check-all')){
                name = $(this).data('option');
            }else{
                name = $(this).attr('name').replace('[]','');
            }

            let optionParts = name.split('_');
            let way = optionParts[2];
            let mode = optionParts[3];
            let module = optionParts[4];

            let allItems = $('.'+name);
            let allItemsValue = [];
            allItems.each(function(){
                if($(this).is(':checked'))
                    allItemsValue.push($(this).val());
                    //allItemsValue[$(this).val()] = $(this).is(':checked');
            });

            let data = {
                action: 'trman_save_option_items',
                name: name,
                way: way,
                mode: mode,
                module: module,
                values: allItemsValue,
                nonce: trman_admin_localize.nonce,
            };

            $.ajax({
                url: ajaxurl,
                data: data,
                method: 'POST',
                success: function(response) {
                    disableSave = false;
                    hideLoadingOverlay();
                },
                error: function() {
                    hideLoadingOverlay();
                }
            });

        });
    });


})(jQuery);
