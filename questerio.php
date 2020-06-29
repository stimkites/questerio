<?php
/**
 * Plugin Name: Questerio
 * Plugin URI: https://wetail.ru/
 * Description: Allows to create lightweight quests easily!
 * Version: 0.0.2
 * Author: Stim
 * Author URI: https://wetail.ru/
 *
 * @package Stim\Questerio
 */

namespace Stim\Questerio;

defined( 'ABSPATH' ) or die( 'Wutta f**k?!' );

/**
 * Constants
 */
define( __NAMESPACE__ . '\ID',          basename( __DIR__ ) . '/' . basename( __FILE__ ) );
define( __NAMESPACE__ . '\SLUG',        basename( __DIR__ ) );
define( __NAMESPACE__ . '\PATH',        dirname( __FILE__ ) );
define( __NAMESPACE__ . '\INDEX',       __FILE__ );
define( __NAMESPACE__ . '\NAME',        basename( __DIR__ ) );
define( __NAMESPACE__ . '\URL',         dirname( plugins_url() ) . '/' . basename( dirname( __DIR__ ) ) . '/' . NAME  );
define( __NAMESPACE__ . '\ASSETS_URL',  URL . '/assets'   );
define( __NAMESPACE__ . '\ASSETS_PATH', PATH . '/assets'  );

const AJAX_H = SLUG . '_ajax';

Questerio::init();

final class Questerio {

    /**
     * Default post type
     */
    const type = 'quest';

    /**
     * Post ID we are attached to
     *
     * @var null
     */
    protected $id = 0;

    /**
     * Post we are attached to
     *
     * @var \WP_Post
     */
    protected $post = null;

    /**
     * Defines if we are on our post page or not
     *
     * @var bool
     */
    private static $me = null;

    /**
     * Create and intialize new quest
     *
     * @param int $post_id
     */
    function __construct( $post_id = 0 ) {
        if( ! $post_id )
            $this->id = wp_insert_post( [
                'post_title'  => __( 'Quest', SLUG ),
                'post_name'   => 'quest',
                'post_status' => 'publish',
                'post_parent' => 0,
                'post_type'   => self::type,
                'guid'        => ''
            ] );
        else $this->id = $post_id;
        $this->post = get_post( $this->id );
    }

    /**
     * Set meta
     *
     * @param $meta_key
     * @param $meta_value
     */
    public function set( $meta_key, $meta_value ){
        update_post_meta( $this->get_id(), $meta_key, $meta_value );
    }

    /**
     * Get meta
     *
     * @param $meta_key
     * @param $default_value
     *
     * @return mixed
     */
    public function get( $meta_key, $default_value = '' ){
        return get_post_meta( $this->get_id(), $meta_key, true ) ?? $default_value;
    }

    /**
     * Get current ID
     *
     * @return int|null
     */
    public function get_id(){
        return $this->id;
    }

    /**
     * Reset questions
     */
    public function reset_questions(){
        $this->set( '_questions', [] );
    }

    /**
     * Set questions
     *
     * @param array $questions
     */
    public function set_questions( $questions = [] ){
        $this->set( '_questions', $questions );
    }

    /**
     * Get questions
     *
     * @return array|mixed
     */
    public function get_questions(){
        if( empty( $this->get( '_questions' ) ) ) return [];
        return  $this->get( '_questions' );
    }

    /**
     * Get total number of questions
     *
     * @return int
     */
    public function get_total_questions(){
        $count = count( $this->get_questions() );
        return $count ? $count : 1;
    }

    /**
     * Set new status
     *
     * @param $new_value
     *
     * @return bool
     */
    public function status( $new_value ){
        $this->post->post_status = $new_value;
        return wp_update_post( [
            'ID'            => $this->id,
            'post_status'   => $new_value
        ] );
    }

    /**
     * Set new title
     *
     * @param $new_value
     *
     * @return bool
     */
    public function title( $new_value ){
        $this->post->post_title = $new_value;
        return true;
    }

    /**
     * Initialize custom post
     */
    static public function init() {

        // Initialize custom post type
        add_action( 'init',                     __CLASS__ . '::register'            );

        // Add custom meta boxes
        add_action( 'add_meta_boxes',           __CLASS__ . '::add_meta_boxes', 30  );
        add_filter( 'get_user_option_meta-box-order_' . self::type,
                                                __CLASS__ . '::metabox_order', 999  );
        // Save custom fields
        add_action( 'save_post_' . self::type,  __CLASS__ . '::save_fields'         );

        // JS & CSS
        add_action( 'admin_enqueue_scripts',    __CLASS__ . '::scripts_be'          );
        add_action( 'wp_enqueue_scripts',       __CLASS__ . '::scripts_fe'          );

        // Listing view
        add_filter( 'manage_edit-' . self::type . '_columns',           __CLASS__ . '::add_columns',    11      );
        add_filter( 'manage_edit-' . self::type . '_sortable_columns',  __CLASS__ . '::sortable_columns'        );
        add_action( 'manage_' . self::type . '_posts_custom_column' ,   __CLASS__ . '::render_columns', 10, 2   );

        // Front end rendering
        add_filter( 'the_content',              __CLASS__ . '::render_front' );

        return true;
    }

    /**
     * Render front end content
     *
     * @param string $content
     *
     * @return string
     */
    public static function render_front( $content ){
        global $post;
        if( $post->post_type !== self::type ) return $content;
        return '<div id="quest-content">'
                . $post->post_excerpt .
                '<button class="button" id="quest-start">' . __( 'Start', SLUG ) . '</button>' .
                '</div>';
    }

    /**
     * Rearrange meta boxes
     *
     * @param array $order
     * @return array
     */
    public static function metabox_order( $order ) {
        $_order = explode( ",", $order['normal'] );
        $new_order = [
            'quest_intro', 'quest_questions', 'quest_answers'
        ];
        foreach( $_order as $o )
            if( ! in_array( $o, $new_order ) )
                $new_order[] = $o;
        $order['normal'] = implode( ",", $new_order );
        return $order;
    }

    /**
     * Enqueue admin styles and scripts
     */
    public static function scripts_be(){
        if( ! self::me() ) return;
        global $post;
        wp_enqueue_editor();
        wp_enqueue_style( 'quest-admin-css', ASSETS_URL . '/css/quests-be.css', [], filemtime( ASSETS_PATH . '/css/quests-be.css' ) );
        wp_register_script( 'quest-admin-js', ASSETS_URL . '/js/quests-be.js', [ 'jquery' ], filemtime( ASSETS_PATH . '/js/quests-be.js' ), true );
        wp_localize_script( 'quest-admin-js', '__quest', [
            'questions' => get_post_meta( $post->ID, '_questions', true ) ?? null,
            'post_type' => self::type,
            'nonce'     => wp_create_nonce( SLUG )
        ] );
        wp_enqueue_script( 'quest-admin-js' );
    }

    /**
     * Enqueue front-end styles and scripts
     */
    public static function scripts_fe(){
        global $post;
        if( $post->post_type !== self::type ) return;
        wp_enqueue_style( 'quest-front-css', ASSETS_URL . '/css/quests-fe.css', [], filemtime( ASSETS_PATH . '/css/quests-fe.css' ) );
        wp_register_script( 'quest-front-js', ASSETS_URL . '/js/quests-fe.js', [ 'jquery' ], filemtime( ASSETS_PATH . '/js/quests-fe.js' ), true );
        wp_localize_script( 'quest-front-js', '__quest', [
            'questions' => get_post_meta( $post->ID, '_questions', true ) ?? null,
        ] );
        wp_enqueue_script( 'quest-front-js' );
    }

    /**
     * Add meta boxes
     */
    public static function add_meta_boxes(){
        add_meta_box( 'quest_intro',     __( 'Description' ),     __CLASS__ . '::render_box_intro',     self::type, 'normal' );
        add_meta_box( 'quest_questions', __( 'Questions', SLUG ), __CLASS__ . '::render_box_questions', self::type, 'normal' );
        add_meta_box( 'quest_answers',   __( 'Answers', SLUG ),   __CLASS__ . '::render_box_answers',   self::type, 'normal' );
    }

    /**
     * Render basic editor
     *
     * @param string $name
     * @param string $content
     * @param int $initial_height
     */
    protected static function render_editor( $name = 'excerpt', $content = '', $initial_height = 275 ){
        wp_editor( htmlspecialchars_decode( $content, ENT_QUOTES ), $name, [
            'textarea_name' => $name,
            'quicktags'     => [ 'buttons' => 'em,strong,link' ],
            'tinymce'       => [
                'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                'theme_advanced_buttons2' => '',
            ],
            'editor_css'    => '<style>#wp-' . $name . '-editor-container .wp-editor-area{height:' . $initial_height . 'px; width:100%;}</style>',
        ] );
    }

    /**
     * Render intro meta box
     *
     * @param \WP_Post $post
     */
    public static function render_box_intro( $post ){
        self::render_editor( 'excerpt', $post->post_excerpt );
    }

    /**
     * Render question meta box
     */
    public static function render_box_questions(){
        ?>
        <div class="quest-question-wrap">
            <?php self::render_quest_controls() ?>
            <div class="quest-question" data-type="question">
                <div class="quest-question-content">
                    <?php self::render_editor( 'question', '', 150 ); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render answer meta box
     */
    public static function render_box_answers(){
        ?>
        <div class="quest-answers-wrap">
            <?php self::render_quest_controls( 'answer' ) ?>
            <div class="quest-answer" data-type="answer">
                <?php self::render_editor( 'answer', '', 75 ); ?>
                <div class="leads-to">
                    <h4><?php _e( 'Leads to', SLUG ) ?>:</h4>
                    <div class="quest-tabs">
                        <input type="radio" name="tabs" id="answer_tab1" class="tab1" checked />
                        <label for="answer_tab1"><?php _e( 'Next quesion', SLUG ) ?></label>
                        <input type="radio" name="tabs" id="answer_tab2" class="tab2"/>
                        <label for="answer_tab2"><?php _e( 'Result page', SLUG ) ?></label>
                        <div class="tab content1">
                            <h5><?php _e( 'Enter next question number here', SLUG ) ?></h5>
                            <label><input
                                        type="number" min="1" max="1000" step="1" pattern="[0-9]"
                                        name="leads_to"
                                        id="leads_to"
                                        value="2"
                                /></label>
                        </div>
                        <div class="tab content2">
                            <h5><?php _e( 'Choose a page to redirect to as a quest result', SLUG ) ?>:</h5>
                            <?php
                            wp_dropdown_pages( [
                                'name'             => 'leads_to_result',
                                'id'               => 'leads_to_result',
                                'sort_column'      => 'menu_order',
                                'sort_order'       => 'ASC',
                                'show_option_none' => ' ',
                                'class'            => 'leads_to_result_selector wc-enhanced-select',
                                'echo'             => true,
                                'selected'         => '',
                                'post_status'      => 'publish,private',
                            ] )
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render controls
     *
     * @param string $data_type
     */
    protected static function render_quest_controls( $data_type = 'question' ){
        ?>
        <div class="quest-controls" data-type="<?php echo $data_type ?>">
            <div class="stats">
                <span class="current">1</span> <?php _e( 'of', SLUG ) ?> <span class="total">1</span>
            </div>
            <div class="control-position">
                <button class="quest-first" title="<?php _e( 'First', SLUG ) ?>"></button>
                <button class="quest-prev"  title="<?php _e( 'Previous', SLUG ) ?>"></button>
                <button class="quest-next"  title="<?php _e( 'Next', SLUG ) ?>" ></button>
                <button class="quest-last"  title="<?php _e( 'Last', SLUG ) ?>" ></button>
            </div>
            <div class="control-insert-add">
                <button class="quest-add-before" title="<?php _e( 'Add new before', SLUG ) ?>"></button>
                <button class="quest-add-after"  title="<?php _e( 'Add new after', SLUG ) ?>" ></button>
            </div>
            <div class="control-copy">
                <button class="quest-copy-to-new" title="<?php _e( 'Copy to new after', SLUG ) ?>"></button>
            </div>
            <div class="control-move">
                <button class="quest-move-left"  title="<?php _e( 'Move left', SLUG )  ?>"></button>
                <button class="quest-move-right" title="<?php _e( 'Move right', SLUG ) ?>"></button>
            </div>
            <div class="control-remove">
                <button class="quest-remove" title="<?php _e( 'Remove', SLUG ) ?>"></button>
            </div>
        </div>
        <?php
    }


    /**
     * Add custom columns to our custom post
     *
     * @param array $columns
     *
     * @return array
     */
    public static function add_columns( $columns ){
        $_columns = [];
        $_columns[ 'cb' ] = $columns[ 'cb' ];
        $_columns[ self::type . '_id' ] = 'ID';
        $_columns = array_merge( $_columns, $columns );
        $_columns[ self::type . '_questions' ] = __( 'Questions' );
        return $_columns;
    }

    /**
     * Make sortable ID column
     *
     * @param $columns
     *
     * @return array
     */
    public static function sortable_columns( $columns ){
        $sortable_columns = [
            self::type . '_questions'           => 'Questions',
            self::type . '_id'                  => 'ID'
        ];
        return wp_parse_args( $sortable_columns, $columns );
    }

    /**
     * Render custom columns
     *
     * @param string $column_slug
     */
    public static function render_columns( $column_slug ){
        global $post;
        switch( $column_slug ) {
            case self::type . '_id':
                $quest = new self($post->ID);
                echo '<a href="/wp-admin/post.php?post=' . $quest->get_id() . '&action=edit">' . $quest->get_id() . '</a>';
            break;
            case self::type . '_questions':
                $quest = new self($post->ID);
                echo '<a href="/wp-admin/post.php?post=' . $quest->get_id() . '&action=edit">' . $quest->get_total_questions() . '</a>';
            break;
        }
    }

    /**
     * Prevent infinite loop on save
     *
     * @var bool
     */
    private static $saved_fields = false;

    /**
     * Save custom fields from metaboxes
     *
     * @param $id
     */
    public static function save_fields( $id ){
        if( self::$saved_fields ) return;
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $id ) ) return;
        if( ! isset( $_POST['quest_save_fields'] ) || ! wp_verify_nonce( $_POST['quest_save_fields'], SLUG ) ) return;
        $quest = new self( $id );
        $quest->reset_questions();
        if( empty( $_POST['question'] ) ) return;
        self::$saved_fields = true;
        $questions = [];
        foreach( $_POST['question'] as $i=>$q ){
            $question = [
                'question'  => $q,
                'answers'   => []
            ];
            if( ! empty( $_POST['question_' . $i . '_answer'] ) )
                foreach( $_POST['question_' . $i . '_answer'] as $a_i=>$a ){
                    $question['answers'][] = [
                        'answer'    => $a,
                        'leads_to'  => [
                            'type'  => $_POST['question_' . $i . '_answer_leads_to_type'][$a_i],
                            'value' => $_POST['question_' . $i . '_answer_leads_to_value'][$a_i],
                            'link'  => (
                                'question' === $_POST['question_' . $i . '_answer_leads_to_type'][$a_i]
                                    ? ''
                                    : get_permalink( $_POST['question_' . $i . '_answer_leads_to_value'][$a_i] )
                            )

                        ]
                    ];
                }
            $questions[] = $question;
        }
        $quest->set_questions( $questions );
    }

    /**
     * Define if we are in our own post
     *
     * @param bool $front
     *
     * @return bool
     */
    private static function me( $front = false ){
        if( null !== self::$me ) return self::$me;
        global $post, $pagenow;
        self::$me = ( $front || 'post-new.php' === $pagenow || 'post.php' === $pagenow ) && (
            isset( $_GET['post_type'] ) && $_GET['post_type'] === self::type ||
            ! empty( $post ) && $post->post_type === self::type
        );
        return self::$me;
    }


    /**
     * Registers the custom post type
     */
    static public function register() {
        $args = apply_filters( 'quest_post_type', [
            'labels'              => [
                'name'               => 'Quests',
                'all_items'          => 'All quests',
                'add_new_item'       => 'New quest'
            ],
            'public'			=> true,
            'show_ui'			=> true,
            '_builtin'			=> false,
            'capability_type'	=> 'post',
            'hierarchical'		=> true,
            'rewrite'			=> false,
            'query_var'			=> false,
            'show_in_menu'		=> true,
            'supports'            => [
                'title', 'thumbnail', 'tags', 'comments', 'author', 'publicize', 'page'
            ],
            'menu_icon'           => 'dashicons-forms',
            'menu_position'       => 64,
            'publicly_queryable'  => true,
            'map_meta_cap'        => true,
            'exclude_from_search' => false,
            'show_in_rest'        => true,
            'taxonomies'          => [ 'category', 'post_tag' ]
        ] );
        register_post_type( self::type, $args );
    }

    /**
     * Return quest custom post type
     *
     * @return string
     */
    public static function post_type(){
        return self::type;
    }

}
