<?php
/**
 * Base class for quick links
 * @author markparnell
 */
abstract class bb_quicklink {
    var $title;
    var $link_template = '<li><a class="s-quicklinks button action %s" href="%s" %s>%s</a></li>';

    abstract public function show_link(array $user_ids, array $args = array());
    public function __construct() {

    }
}

/**
 * Base class for modal quick links
 * @author markparnell
 */
abstract class bb_modal_quicklink extends bb_quicklink {
    var $modal_id;
    var $trigger_export = false;

    abstract protected function form_contents(array $user_ids = array(), array $args = array());
    abstract static public function post_submission();

    public function __construct() {
        $this->modal_id = wp_generate_password(6, false);
    }

    /**
     * Outputs the quicklink button
     */
    public function show_link(array $user_ids, array $args = array()) {
        $url = '#TB_inline?width=600&height=550&inlineId='.$this->modal_id;
        printf($this->link_template, 'thickbox', $url, $attrs, $this->title);

        $this->output_modal($user_ids, $args);
    }

    /**
     * Outputs the thickbox modal and the required javascript
     * @see self::form_contents()
     */
    protected function output_modal(array $user_ids, array $args = array()) {
        add_thickbox(); // Make sure modal library is loaded
        $function_name = get_class($this).'_action_submit';
?>
        <div id="<?php echo $this->modal_id; ?>" style="display: none;">
            <div>
                <h2><?php echo $this->title; ?></h2>
                <form action="" method="post">
                    <?php $this->form_contents($user_ids, $args); ?>
                    <br><input type="submit" class="button action" onclick="return <?php echo $function_name; ?>();" value="Submit">
                </form>
            </div>
        </div>
        <script type="text/javascript">
            function <?php echo $function_name; ?>() {
                var tableName = '<?php echo $this->title; ?>';
                var data = {
                        'action': '<?php echo get_class($this); ?>_submit',
                        'user_ids': '<?php echo implode(',', $user_ids); ?>'
<?php
        foreach ($args as $key => $data) {
            if (is_array($data)) {
                $data = implode(',', $data);
            }
?>
                        ,'<?php echo $key; ?>': '<?php echo $data; ?>'
<?php
        }
?>
                };
                jQuery('#TB_ajaxContent form').find('textarea, input, select').each(function() {
                    var element = jQuery(this);
                    var fieldName = element.attr('name');
                    if (typeof fieldName !== 'undefined') {
                        if (element.hasClass('wp-editor-area') && tinymce.get(fieldName) !== null) {
                        	data[fieldName] = tinymce.get(fieldName).getContent();
                        } else {
                            data[fieldName] = element.val();
                        }
                    }
                });
            	jQuery.post(ajaxurl, data, function(response) {
        			if (response == 0) {
            			var appendTableName = jQuery('#TB_ajaxContent form').find('select.append_table_name').first().children(':selected').text();
            			if (appendTableName != '') {
            				tableName += ' - '+appendTableName;
            			}
                        tb_remove();
<?php
        if ($this->trigger_export) {
?>
                        jQuery('.wp-list-table').tableExport({
                        	tableName:tableName, type:'excel', escape:'false', htmlContent:'false'
                        });
<?php
        } else {
?>
                        window.location.reload();
<?php
        }
?>
        			} else {
            			alert(response);
        			}
        		});
                return false;
            }
        </script>
<?php
    }

    /**
     * Add new history note to user(s)
     * @param string $title Note title
     * @param string $contents Note contents
     * @param integer $type Term ID of primary note type
     * @param integer $subtype Term ID of secondary note type
     * @param array $user_ids List of user IDs to add note to
     */
    public static function add_note($title, $contents, $type, $subtype, array $user_ids, array $args = array(), $action_required = false) {
        foreach ($user_ids as $user_id) {
            $data = array(
                    'post_type' => 'bb_note',
                    'post_title' => $title,
                    'post_content' => $contents,
                    'post_status' => 'publish',
                    'post_author' => $user_id,
                    'tax_input' => array(
                            'bb_note_type' => array(
                                    $type,
                                    $subtype,
                            ),
                    ),
            );

            $data = array_merge_recursive($data, $args);

            $new_post = wp_insert_post($data);
            if ($action_required) {
                add_post_meta($new_post, '_bbc_action_required', 'true');
            }
        }
        return true;
    }

    /**
     * Output select boxes for note type and sub-type
     */
    protected function output_note_type_selects() {
        $note_types = get_terms('bb_note_type', array('hide_empty' => false));
        $parent_types = $child_types = '<option value="" class="please_select">Please Select</option>';
        foreach ($note_types as $note_type) {
            $note_option = '<option value="'.$note_type->term_id.'" class="childof_'.$note_type->parent.'">'.$note_type->name.'</option>';
            if ($note_type->parent == 0) {
                $parent_types .= $note_option;
            } else {
                $child_types .= $note_option;
            }
        }
?>
        <div class="modal-row"><label for="note_type">Note Type:</label><select id="note_type" name="note_type"><?php echo $parent_types ?></select></div>
        <div class="modal-row"><label for="note_subtype">Note Sub-Type:</label><select id="note_subtype" name="note_subtype"><?php echo $child_types ?></select></div>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                filter_subtypes();
                jQuery('select[name="note_type"]').on('change', function() {
                    filter_subtypes();
                });
            });
            function filter_subtypes() {
                var e = jQuery('#TB_ajaxContent select[name="note_type"]');
                if (!e) {
                	e = jQuery('select[name="note_type"]');
                }
                jQuery('#note_subtype option:not(.please_select)').hide();
                jQuery('#note_subtype option.childof_'+e.find(':selected').val()).show();
            }
        </script>
<?php
    }
}

/**
 * Base class for external page quick links
 * @author markparnell
 */
abstract class bb_page_quicklink extends bb_quicklink {
    var $url;

    public function show_link(array $user_ids, array $args = array()) {
        printf($this->link_template, '', $this->url, ' target="_blank"', $this->title);
    }
}