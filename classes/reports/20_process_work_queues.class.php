<?php
if (function_exists('bbconnect_workqueues_insert_action_item')) {
    /**
     * Process Work Queues quicklink
     * @author markparnell
     *
     */
    class reports_20_process_work_queues_quicklink extends bb_modal_quicklink {
        public function __construct() {
            parent::__construct();
            $this->title = 'Process Work Queue(s)';
        }

        protected function form_contents(array $user_ids = array(), array $args = array()) {
            echo '<div class="modal-row"><label for="work_queues" class="full-width">Which Work Queue(s) do you want to process?</label><br>';
            $work_queues = array();
            foreach ($args['note_ids'] as $note_id) {
                $note_queues = wp_get_post_terms($note_id, 'bb_note_type');
                foreach ($note_queues as $note_queue) {
                    if ($note_queue->parent > 0) {
                        $work_queues[$note_queue->term_id] = $note_queue->name;
                    }
                }
            }
            foreach ($work_queues as $queue_id => $queue_name) {
                echo '<input type="checkbox" name="work_queues['.$queue_id.']" value="'.$queue_id.'" checked> '.$queue_name.'<br>';
            }
            echo '</div>';
            echo '<div class="modal-row"><label for="comments">Comments:</label><textarea id="comments" name="comments" rows="10"></textarea></div>';
        }

        public static function post_submission() {
            extract($_POST);
            if (empty($comments) || empty($work_queues)) {
                echo 'All fields are required.';
                return;
            } elseif (empty($note_ids)) {
                echo 'No work queue items found.';
                return;
            }

            $note_ids = explode(',', $note_ids);

            // Get required terms
            $system_term = get_term_by('slug', 'system', 'bb_note_type');
            $closed_action_term = get_term_by('slug', 'closed-action', 'bb_note_type');

            // Loop through notes
            foreach ($note_ids as $note_id) {
                $note = get_post($note_id);

                foreach ($work_queues as $work_queue) {
                    if (has_term($work_queue, 'bb_note_type', $note)) {
                        // Add new note
                        $post_content = $comments."\n\n".'Closed action "'.$note->post_title.'" from '.$note->post_date;
                        $data = array(
                                'post_type' => 'bb_note',
                            	'post_title' => 'Closed Action',
                                'post_content' => $post_content,
                                'post_status' => 'publish',
                                'post_author' => $note->post_author,
                                'tax_input' => array(
                                        'bb_note_type' => array(
                                                $system_term->term_id,
                                                $closed_action_term->term_id,
                                        )
                                )
                        );

                        $new_post = wp_insert_post($data);

                        // Set parent ID on original action
                        if ($new_post) {
                            $note->post_parent = $new_post;
                            $note->post_content .= "\n\n".'Actioned on '.date('Y-m-d').' with the following comment:'."\n\n".$comments;
                            wp_update_post($note);
                            delete_post_meta($note_id, '_bbc_action_required');
                        } else {
                            echo 'Failed to process work queue. Please try again.';
                            return;
                        }
                        continue(2);
                    }
                }
            }
            return true;
        }
    }
}