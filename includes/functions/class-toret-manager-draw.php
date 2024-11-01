<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('Toret_Manager_Draw_Functions')) {

    class Toret_Manager_Draw_Functions
    {

        /**
         * Draw module title with enable/disable checkbox
         *
         * @param string $title
         * @param string $option
         * @param string $slug
         * @param string $target
         * @param string $module
         * @param string $endpoint
         */
        function draw_module_title_with_checkbox(string $title, string $option, string $slug, string $target, string $module, string $endpoint)
        {
            ?>
            <div class="trman-admin-container-title-wrap" id="m-<?php echo esc_attr($module); ?>">
                <h2><?php echo esc_html($title); ?></h2>
                <label class="trman-toggle-switch">
                    <input value="ok" name="<?php echo esc_attr($option); ?>"
                           id="<?php echo esc_attr($option); ?>" <?php echo esc_attr((get_option($option) == 'ok' ? 'checked="checked"' : "")); ?>
                           type="checkbox">
                    <span data-endpoint="<?php echo esc_attr($endpoint); ?>"
                          data-module="<?php echo esc_attr($module); ?>"
                          data-option="<?php echo esc_attr($option); ?>" data-target="<?php echo esc_attr($target); ?>"
                          class="trman-toggle-slider trman-toggle-slider-round"></span>
                </label>
            </div>
            <?php
        }

        /**
         * Remove special characters from string
         *
         * @param string $string
         * @return array|string|string[]|null
         */
        function clean_string(string $string)
        {
            $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
            return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
        }

        /**
         * Draw input text option row
         *
         * @param string $title
         * @param string $option
         * @param string $inputclass
         * @param string $required
         * @param string $rowspan
         * @param string $labelcellclass
         * @param string $rowclass
         * @param bool $tooltip
         * @param string $tooltiptext
         * @param string $inputcellclass
         */
        function draw_input_text_row(string $title, string $option, string $inputclass = '', string $required = '', string $rowspan = '', string $labelcellclass = '', string $rowclass = '', bool $tooltip = false, string $tooltiptext = '', string $inputcellclass = '')
        {
            echo '<tr class="' . esc_attr($rowclass) . '">';
            echo '<th class="' . esc_attr($labelcellclass) . '" rowspan="' . esc_attr($rowspan) . '">';
            echo '<label for="' . esc_attr($option) . '">' . esc_html($title) . '</label>';

            if ($tooltip) {
                echo '<div class="tooltip"><span class="dashicons dashicons-info"></span><span class="trman-input-tooltiptext">' . esc_html($tooltiptext) . '</span></div>';
            }

            echo '</td>';

            echo '<td class="' . esc_attr($inputcellclass) . '"><input type="text" class="' . esc_attr($inputclass) . ' trman-option" name="' . esc_attr($option) . '" id="' . esc_attr($option) . '" value="' . esc_attr((!empty(get_option($option)) ? get_option($option) : "")) . '" ' . esc_attr($required) . '/></td>';
            echo '</tr>';
        }

        /**
         * Draw input info option row
         *
         * @param string $title
         * @param string $option
         * @param string $code
         * @param string $inputclass
         * @param string $required
         * @param string $rowspan
         * @param string $labelcellclass
         * @param string $rowclass
         * @param bool $tooltip
         * @param string $tooltiptext
         * @param string $inputcellclass
         */
        function draw_code_info_row(string $title, string $option, string $code, string $inputclass = '', string $required = '', string $rowspan = '', string $labelcellclass = '', string $rowclass = '', bool $tooltip = false, string $tooltiptext = '', string $inputcellclass = '')
        {
            echo '<tr class="' . esc_attr($rowclass) . '">';
            echo '<th class="' . esc_attr($labelcellclass) . '" rowspan="' . esc_attr($rowspan) . '">';
            echo '<label for="' . esc_attr($option) . '">' . esc_html($title) . '</label>';

            if ($tooltip) {
                echo '<div class="tooltip"><span class="dashicons dashicons-info"></span><span class="trman-input-tooltiptext">' . esc_html($tooltiptext) . '</span></div>';
            }

            echo '</td>';

            echo '<td class="' . esc_attr($inputcellclass) . '"><code>' . esc_html($code) . '</code></td>';
            echo '</tr>';
        }

        /**
         * Draw input checkbox option row
         *
         * @param string $title
         * @param string $option
         * @param bool $raw
         * @param string $custom_name
         * @param string $class
         * @param string $target
         * @param string $labelcellclass
         * @param string $inputcellclass
         * @param string $anchor_target
         * @param bool $tooltip
         * @param string $tooltiptext
         */
        function draw_input_checkbox_row(string $title, string $option, bool $raw = false, string $custom_name = '', string $class = '', string $target = '', string $labelcellclass = '', string $inputcellclass = '', string $anchor_target = '', bool $tooltip = false, string $tooltiptext = '',$default = "")
        {
            if ($raw) {
                $value = $option;
            } else {
                $value = get_option($option,$default);
            }

            $name = $option;
            if ($custom_name != '') {
                $name = $custom_name;
            }

            if ($tooltip) {
                $tooltip_html = '<span class="trman-input-tooltiptext">' . esc_html($tooltiptext) . '</span>';
            }else{
                $tooltip_html = '';
            }

            echo '
            <tr>
                <th data-target="' . esc_attr($anchor_target) . '" class="' . esc_attr($labelcellclass) . '" ><div class="trman-cell-tooltip"><label for="' . esc_attr($option) . '">' . esc_html($title) . '</label>' . wp_kses_post($tooltip_html) . '</div></th>
                <td class="' . esc_attr($inputcellclass) . '"><input data-target="' . esc_attr($target) . '" type="checkbox" class="' . esc_attr($class) . ' trman_module_review_get_parent trman-option" name="' . esc_attr($name) . '" id="' . esc_attr($option) . '" value="ok" ' . ($value == 'ok' ? 'checked="checked"' : '') . '/></td>
            </tr>
			';
        }

        /**
         * Imported item status
         *
         * @param string $title
         * @param string $option
         * @param string $endpoint
         * @param string $inputclass
         * @param string $required
         * @param string $rowspan
         * @param string $labelcellclass
         * @param string $rowclass
         * @param bool $tooltip
         * @param string $tooltiptext
         * @param string $inputcellclass
         */
        function draw_imported_status_row(string $title, string $option, string $endpoint, string $inputclass = '', string $required = '', string $rowspan = '', string $labelcellclass = '', string $rowclass = '', bool $tooltip = false, string $tooltiptext = '', string $inputcellclass = '')
        {
            echo '<tr class="' . esc_attr($rowclass) . '">';
            echo '<th class="' . esc_attr($labelcellclass) . '" rowspan="' . esc_attr($rowspan) . '">';
            echo '<label for="' . esc_attr($option) . '">' . esc_html($title) . '</label>';

            if ($tooltip) {
                echo '<div class="tooltip"><span class="dashicons dashicons-info"></span><span class="trman-input-tooltiptext">' . esc_html($tooltiptext) . '</span></div>';
            }

            echo '</td>';

            echo '<td class="' . esc_attr($inputcellclass) . '">';
            echo '<select type="select" class="' . esc_attr($inputclass) . ' trman-option" name="' . esc_attr($option) . '" id="' . esc_attr($option) . '" >';

            if ($endpoint == 'Product') {
                $statuses = get_post_statuses();
            } elseif ($endpoint == 'Comment') {
                $statuses = get_comment_statuses();
            } else {
                $statuses = get_post_statuses();
            }

            echo '<option value="default" ' . esc_attr((get_option($option, 'default') == 'default' ? 'selected' : '')) . '>' . esc_html__('Default', 'toret-manager') . '</option>';
            foreach ($statuses as $status => $title) {
                echo '<option value="' . esc_attr($status) . '" ' . esc_attr((get_option($option) == $status ? 'selected' : '')) . '>' . esc_html($title) . '</option>';
            }
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }

        /**
         * Draw properties checkboxes
         *
         * @param string $module
         * @param array $data
         * @param string $option_checkbox
         * @param array $default
         * @param string $option
         * @param string $slug
         * @param string $class
         * @param string $target
         * @param string $way
         */
        function draw_properties_checkboxes(string $module, array $data, string $option_checkbox, array $default, string $option, string $slug, string $class, string $target, string $way = 'upload')
        {
            $option_checkbox_all = $option_checkbox . '_all';

            $all_checked = get_option($option_checkbox_all, 'ok') == 'ok';
            $selected_checked = get_option($option_checkbox, '') == 'ok';

            $show_properties = false;

            if ($selected_checked && !$all_checked) {
                $show_properties = true;
            }

            $hide_check_all = false;
            if (count($data) == 0) {
                $data = $default;
                $hide_check_all = true;
            }

            ?>
            <tr id="<?php echo esc_attr($target) ?>"
                style="<?php echo esc_attr((!$show_properties ? 'display:none' : '')) ?>">
                <td></td>
                <td class="trman-properties-cell">

                    <div data-target="<?php echo esc_attr($target); ?>"
                         class="<?php echo esc_attr($class); ?> trman_data_collapsible trman_data_properties">

                        <div class="trman-properties-wrap">

                            <div style="<?php echo($hide_check_all ? "display:none" : "") ?>">
                                <label>
                                    <input id="<?php echo(esc_attr($target . '-checkall')); ?>" class="trman-check-all trman-option-items"
                                           data-target="<?php echo esc_attr($target); ?>"
                                           data-option="<?php echo esc_attr($option); ?>"
                                           data-way="<?php echo esc_attr($way); ?>"
                                           type="checkbox"/><?php esc_html_e('Select all', 'toret-manager'); ?>
                                </label>
                            </div>

                            <div class="trman-properties">

                                <?php

                                $saved = get_option($option, $default);

                                $Toret_Manager_i18n = new Toret_Manager_i18n();

                                if ($module == 'stock') {
                                    foreach ($data as $item => $mandatory) {

                                        $checked = get_option($item, '') == 'ok' ? "checked" : "";

                                        ?>
                                        <div>
                                            <label>
                                                <input class="<?php echo esc_attr($item); ?> trman-option"
                                                    <?php echo esc_attr($checked); ?>
                                                       value="ok" type="checkbox"
                                                       name="<?php echo esc_attr($item); ?>"/><?php echo esc_html($Toret_Manager_i18n->get_prop_trans($item)); ?>
                                            </label>
                                        </div>
                                        <?php
                                    }

                                } else {

                                    foreach ($data as $item => $mandatory) {

                                        $checked = "";
                                        $exclude = false;
                                        if (!empty($mandatory)) {
                                            $properties = explode(';', $mandatory);
                                            $checked = $properties[0] == 'x' ? 'readonly checked onclick="return false;"' : '';
                                            $exclude = $properties[1] == 'x';
                                        }

                                        if ($exclude) {
                                            continue;
                                        }

                                        ?>
                                        <div>
                                            <label>
                                                <input class="<?php echo esc_attr($option); ?> trman-option-items"
                                                       multiple <?php echo $checked; ?><?php echo esc_attr((in_array($item, $saved) ? 'checked' : '')); ?>
                                                       value="<?php echo esc_attr($item); ?>" type="checkbox"
                                                       name="<?php echo esc_attr($option); ?>[]"/><?php echo esc_html($Toret_Manager_i18n->get_prop_trans($item)); ?>
                                            </label>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>

                            </div>
                        </div>
                </td>
            </tr>
            <?php
        }

        /**
         * Draw input checkbox option row
         *
         * @param string $title
         * @param string $option
         * @param string $class
         * @param string $target
         * @param string $labelcellclass
         * @param string $inputcellclass
         * @param string $anchor_target
         * @param bool $tooltip
         * @param string $tooltiptext
         */
        function draw_sync_row(string $title, string $option, string $class = '', string $target = '', string $labelcellclass = '', string $inputcellclass = '', string $anchor_target = '', bool $tooltip = false, string $tooltiptext = '')
        {
            $option_all = $option . '_all';

            $value = get_option($option, '');
            $value_all = get_option($option_all, 'ok');

            $name = $option;
            $name_all = $option_all;


            if ($tooltip) {
                $tooltip_html =  '<div class="trman-input-tooltip"><span class="dashicons dashicons-info"></span><span class="trman-input-tooltiptext">' . esc_html($tooltiptext) . '</span></div>';
            }else{
                $tooltip_html = "";
            }

            echo '<tr>';
            echo '<th data-target="' . esc_attr($anchor_target) . '" class="' . esc_attr($labelcellclass) . '" ><label for="' . esc_attr($option) . '">' . esc_html($title) . '</label>' . wp_kses_post($tooltip_html) . '</th>';
            echo '<td class="' . esc_attr($inputcellclass) . '">';
            echo '<input class="trman-prop-main-checbox-all trman-option trman-option-all" data-target="' . esc_attr($target) . '" type="checkbox" class="' . esc_attr($class) . '" name="' . esc_attr($name_all) . '" id="' . esc_attr($option_all) . '" value="ok" ' . esc_attr(($value_all) == 'ok' ? 'checked="checked"' : '') . '/><label for="' . esc_attr($option_all) . '">' . esc_html__('Synchronize all', 'toret-manager') . '</label>';
            echo '<input class="trman-prop-main-checbox trman-option trman-option-partial" data-target="' . esc_attr($target) . '" type="checkbox" class="' . esc_attr($class) . '" name="' . esc_attr($name) . '" id="' . esc_attr($option) . '" value="ok" ' . esc_attr(($value) == 'ok' ? 'checked="checked"' : '') . '/><label for="' . esc_attr($option) . '">' . esc_html__('Sync selected metadata fields', 'toret-manager') . '</label>';
            echo '</td>';
            echo '</tr>';
        }

        /**
         * Draw input checkbox option row
         *
         * @param string $title
         * @param string $option
         * @param string $class
         * @param string $target
         * @param string $labelcellclass
         * @param string $inputcellclass
         * @param string $anchor_target
         * @param bool $tooltip
         * @param string $tooltiptext
         */
        function draw_stock_sync_row(string $title, string $option, string $class = '', string $target = '', string $labelcellclass = '', string $inputcellclass = '', string $anchor_target = '', bool $tooltip = false, string $tooltiptext = '')
        {
            $option_all = $option . '_all';

            $value = get_option($option, '');
            $value_all = get_option($option_all, 'ok');

            $name = $option;
            $name_all = $option_all;


            if ($tooltip) {
                $tooltip_html =  '<div class="trman-input-tooltip"><span class="dashicons dashicons-info"></span><span class="trman-input-tooltiptext">' . esc_html($tooltiptext) . '</span></div>';
            }else{
                $tooltip_html = "";
            }

            echo '<tr>';
            echo '<th data-target="' . esc_attr($anchor_target) . '" class="' . esc_attr($labelcellclass) . '" ><label for="' . esc_attr($option) . '">' . esc_html($title) . '</label>' . wp_kses_post($tooltip_html) . '</th>';
            echo '<td class="' . esc_attr($inputcellclass) . '">';
            echo '<input class="trman-prop-main-checbox-all trman-option trman-option-all" data-target="' . esc_attr($target) . '" type="checkbox" class="' . esc_attr($class) . '" name="' . esc_attr($name_all) . '" id="' . esc_attr($option_all) . '" value="ok" ' . esc_attr(($value_all == 'ok' ? 'checked="checked"' : '')) . '/><label for="' . esc_attr($option_all) . '">' . esc_html__('Enable all', 'toret-manager') . '</label>';
            echo '<input class="trman-prop-main-checbox trman-option trman-option-partial" data-target="' . esc_attr($target) . '" type="checkbox" class="' . esc_attr($class) . '" name="' . esc_attr($name) . '" id="' . esc_attr($option) . '" value="ok" ' . esc_attr(($value == 'ok' ? 'checked="checked"' : '')) . '/><label for="' . esc_attr($option) . '">' . esc_html__('Synchronize selected', 'toret-manager') . '</label>';
            echo '</td>';
            echo '</tr>';

        }

        /**
         * Draw input checkbox option row
         *
         * @param string $title
         * @param string $option
         * @param bool $raw
         * @param string $custom_name
         * @param string $class
         * @param string $target
         * @param string $labelcellclass
         * @param string $inputcellclass
         * @param string $anchor_target
         * @param bool $tooltip
         * @param string $tooltiptext
         */
        function draw_sync_delete_row(string $title, string $option, bool $raw = false, string $custom_name = '', string $class = '', string $target = '', string $labelcellclass = '', string $inputcellclass = '', string $anchor_target = '', bool $tooltip = false, string $tooltiptext = '')
        {
            if ($raw) {
                $value = $option;
            } else {
                $value = get_option($option);
            }

            $name = $option;
            if ($custom_name != '') {
                $name = $custom_name;
            }


            if ($tooltip) {
                $tooltip_html =  '<div class="trman-input-tooltip"><span class="dashicons dashicons-info"></span><span class="trman-input-tooltiptext">' . esc_html($tooltiptext) . '</span></div>';
            }else{
                $tooltip_html = "";
            }


            echo '<tr>';
            echo '<th data-target="' . esc_attr($anchor_target) . '" class="' . esc_attr($labelcellclass) . '" ><label for="' . esc_attr($option) . '">' . esc_html($title) . '</label>' . wp_kses_post($tooltip_html) . '</th>';
            echo '<td class="' . esc_attr($inputcellclass) . '">';
            echo '<input class="trman-prop-main-checbox trman-option" data-target="' . esc_attr($target) . '" type="checkbox" class="' . esc_attr($class) . 'trman-delete" name="' . esc_attr($name) . '" id="' . esc_attr($option) . '" value="ok" ' . esc_attr(($value == 'ok' ? 'checked="checked"' : '')) . '/><label for="' . esc_attr($option) . '">' . esc_html__('Synchronize', 'toret-manager') . '</label>';
            echo '</td>';
            echo '</tr>';
        }

        /**
         * Get class for API check message
         *
         * @return string
         */
        function get_api_check_msg_class(): string
        {
            $api_check = get_option('trman_api_check', 'verify');
            return 'toret-net-' . $api_check;
        }

        /**
         * Admin input text field
         *
         * @param array $field
         */
        static function custom_wp_text_input(array $field)
        {
            $field['placeholder'] = isset($field['placeholder']) ? $field['placeholder'] : '';
            $field['class'] = isset($field['class']) ? $field['class'] : 'short';
            $field['style'] = isset($field['style']) ? $field['style'] : '';
            $field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
            $field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
            $field['type'] = isset($field['type']) ? $field['type'] : 'text';
            $field['desc_tip'] = isset($field['desc_tip']) ? $field['desc_tip'] : false;

            // Custom attribute handling
            $custom_attributes = array();

            if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {

                foreach ($field['custom_attributes'] as $attribute => $value) {
                    $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
                }
            }

            echo('<p class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '">
		<label for="' . esc_attr($field['id']) . '">' . wp_kses_post($field['label']) . '</label>');

            if (!empty($field['description']) && false !== $field['desc_tip'] && Toret_Manager_Helper::is_woocommerce_active()) {
                echo(wc_help_tip($field['description']));
            }

            echo('<input type="' . esc_attr($field['type']) . '" class="' . esc_attr($field['class']) . '" style="' . esc_attr($field['style']) . '" name="' . esc_attr($field['name']) . '" id="' . esc_attr($field['id']) . '" value="' . esc_attr($field['value']) . '" placeholder="' . esc_attr($field['placeholder']) . '" ' . esc_attr(implode(' ', $custom_attributes)) . ' /> ');

            if (!empty($field['description']) && false === $field['desc_tip']) {
                echo('<span class="description">' . wp_kses_post($field['description']) . '</span>');
            }

            echo('</p>');
        }

        /**
         * Admin checkbox field
         *
         * @param array $field
         */
        static function custom_wp_checkbox(array $field)
        {
            $field['class'] = isset($field['class']) ? $field['class'] : 'checkbox';
            $field['style'] = isset($field['style']) ? $field['style'] : '';
            $field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
            $field['cbvalue'] = isset($field['cbvalue']) ? $field['cbvalue'] : 'yes';
            $field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
            $field['desc_tip'] = isset($field['desc_tip']) ? $field['desc_tip'] : false;

            // Custom attribute handling
            $custom_attributes = array();

            if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {

                foreach ($field['custom_attributes'] as $attribute => $value) {
                    $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
                }
            }

            echo('<p class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '">
		<label for="' . esc_attr($field['id']) . '">' . wp_kses_post($field['label']) . '</label>');

            if (!empty($field['description']) && false !== $field['desc_tip'] && Toret_Manager_Helper::is_woocommerce_active()) {
                echo(wc_help_tip($field['description']));
            }

            echo('<input type="checkbox" class="' . esc_attr($field['class']) . '" style="' . esc_attr($field['style']) . '" name="' . esc_attr($field['name']) . '" id="' . esc_attr($field['id']) . '" value="' . esc_attr($field['cbvalue']) . '" ' . checked($field['value'], $field['cbvalue'], false) . '  ' . esc_attr(implode(' ', $custom_attributes)) . '/> ');

            if (!empty($field['description']) && false === $field['desc_tip']) {
                echo('<span class="description">' . wp_kses_post($field['description']) . '</span>');
            }

            echo('</p>');
        }
    }
}