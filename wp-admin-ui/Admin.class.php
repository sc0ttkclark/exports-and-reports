<?php
if ( ! defined( 'WP_ADMIN_UI_EXPORT_DIR' ) ) {
	define( 'WP_ADMIN_UI_EXPORT_DIR', WP_CONTENT_DIR . '/exports' );
}

global $wpdb;

if ( ! is_object( $wpdb ) ) {
	wp_die( 'Access denied' );
}
// FOR EXPORTS ONLY
if ( isset( $_GET['exports_and_reports_download'] ) && isset( $_GET['_wpnonce'] ) && false !== wp_verify_nonce( $_GET['_wpnonce'], 'wp-admin-ui-export' ) ) {
    do_action('wp_admin_ui_export_download');
    $file = WP_ADMIN_UI_EXPORT_DIR.'/'.str_replace(array('/','..'),'',$_GET['exports_and_reports_export']);

    $file = realpath( $file );

	if ( ! isset( $_GET['exports_and_reports_export'] ) || empty( $_GET['exports_and_reports_export'] ) || ! file_exists( $file ) ) {
		wp_die( 'File not found.' );
	}

    wp_redirect( str_replace( WP_ADMIN_UI_EXPORT_DIR, WP_ADMIN_UI_EXPORT_URL, $file ) );
    die();

    // required for IE, otherwise Content-disposition is ignored
	if ( ini_get( 'zlib.output_compression' ) ) {
        ini_set('zlib.output_compression','Off');
	}

    header("Pragma: public"); // required
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false); // required for certain browsers
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=\"".basename($file)."\";" );
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($file));
    flush();
    readfile("$file");
    exit();
}
/**
 * Admin UI class for WordPress plugins
 *
 * Creates a UI for any plugin screens within WordPress
 *
 * NOTE: If you are including this class code in a plugin,
 * consider renaming the class to avoid conflicts with other plugins.
 * This is not required, but some developers may include the class
 * the wrong way which could cause an issue with your including the
 * class file.
 *
 * @package Admin UI for Plugins
 *
 * @version 1.9.8.20171011
 * @author Scott Kingsley Clark
 * @link https://www.scottkclark.com/
 *
 * @param mixed $options
 */
class WP_Admin_UI
{
    // base
    var $table = false;
    var $identifier = 'id';
    var $sql = false;
    var $sql_count = false;
    var $id = false;
    var $action = 'manage';
    var $do = false;
    var $search = true;
    var $filters = array();
    var $search_query = false;
    var $pagination = true;
    var $page = 1;
    var $limit = 25;
    var $order = false;
    var $order_dir = 'DESC';
    var $reorder_order = false;
    var $reorder_order_dir = 'ASC';
    var $api = false;

    // ui
    var $item = 'Item';
    var $items = 'Items';
    var $heading = array('manage'=>'Manage','add'=>'Add New','edit'=>'Edit','duplicate'=>'Duplicate','view'=>'View','reorder'=>'Reorder');
    var $icon = false;
    var $css = false;

    // actions
    var $add = true;
    var $view = false;
    var $edit = true;
    var $duplicate = false;
    var $delete = true;
    var $save = true;
    var $readonly = false;
    var $export = false;
    var $reorder = false;

    // array of custom functions to run for actions
    var $custom = array();

    // data related
    var $total = 0;
    var $totals = false;
    var $columns = array();
    var $data = array();
    var $full_data = array();
    var $row = array();
    var $default_none = false;
    var $search_columns = array();
    var $form_columns = array();
    var $view_columns = array();
    var $export_columns = array();
    var $reorder_columns = array();
    var $insert_id = 0;
    var $related = array();

    // export related
    var $exported_file = false;
    var $export_url = false;
    var $export_type = false;
    var $export_delimiter = false;

    function __construct ($options=false)
    {
        do_action('wp_admin_ui_pre_init',$options);
        $options = $this->do_hook('options',$options);
        $this->base_url = plugins_url( 'Admin.class.php', __FILE__  );
        $this->export_url = admin_url( 'admin-ajax.php' ) . '?action=wp_admin_ui_export&exports_and_reports_download=1&_wpnonce='.wp_create_nonce('wp-admin-ui-export').'&exports_and_reports_export=';
        $this->assets_url = str_replace('/Admin.class.php','',$this->base_url).'/assets';
        if(false!==$this->get_var('id'))
            $this->id = sanitize_text_field( $_GET['id'] );
        if(false!==$this->get_var('action',false,array('add','edit','duplicate','view','delete','manage','reorder','export')))
            $this->action = sanitize_text_field( $_GET['action'] );
        if(false!==$this->get_var('do',false,array('save','create')))
            $this->do = sanitize_text_field( $_GET['do'] );
        if(false!==$this->get_var('search_query'))
            $this->search_query = sanitize_text_field( $_GET['search_query'] );
        if(false!==$this->get_var('pg'))
            $this->page = absint( $_GET['pg'] );
        if(false!==$this->get_var('limit'))
            $this->limit = absint( $_GET['limit'] );
        if(false!==$this->get_var('order')) {
	        $order = sanitize_text_field( $_GET[ 'order' ] );
	        $order = preg_replace( "/([- ])/", "_", trim( $order ) );
		    $order = preg_replace( "/([^0-9a-z_])/", "", strtolower( $order ) );
		    $order = preg_replace( "/(_){2,}/", "_", $order );

	        $this->order = $order;
        }
        if(false!==$this->get_var('order_dir',false,array('ASC','DESC')))
            $this->order_dir = ('ASC'==$_GET['order_dir']?'ASC':'DESC');
        if(false!==$this->get_var('action',false,'export')&&false!==$this->get_var('export_type',false,array('csv','tsv','pipe','custom','xml','json')))
            $this->export_type = $_GET['export_type'];
        if(false!==$this->get_var('action',false,'export')&&'custom'==$this->export_type&&false!==$this->get_var('export_delimiter'))
            $this->export_delimiter = $_GET['export_delimiter'];

        if(false!==$options&&!empty($options))
        {
            if(!is_array($options))
                parse_str($options,$options);
            foreach($options as $option=>$value)
                $this->$option = $value;
        }
        if(false!==$this->readonly)
            $this->add = $this->edit = $this->delete = $this->save = $this->reorder = false;
        if(false!==$this->reorder&&false===$this->reorder_order)
            $this->reorder_order = $this->reorder;
        if (!empty($this->columns))
            $this->columns = $this->setup_columns();
        if(!empty($this->filters)&&!is_array($this->filters))
            $this->filters = implode(',',$this->filters);
        $this->do_hook('post_init',$options);
    }
    function get_var ($index,$default=false,$allowed=false,$array=false)
    {
        if(!is_array($array))
        {
            if($array=='post')
                $array = $_POST;
            else
                $array = $_GET;
        }
        if(false!==$allowed&&!is_array($allowed))
            $allowed = array($allowed);
        $value = $default;
        if(isset($array[$index])&&(false===$allowed||in_array($array[$index],$allowed)))
            $value = $array[$index];
        return $this->do_hook('get_var',$value,$index,$default,$allowed,$array);
    }
    function hidden_vars ($exclude=false)
    {
        $exclude = $this->do_hook('hidden_vars',$exclude);
        if(false===$exclude)
            $exclude = array();
        if(!is_array($exclude))
            $exclude = explode(',',$exclude);
        foreach($_GET as $k=>$v)
        {
            if(in_array($k,$exclude))
                continue;
?>
<input type="hidden" name="<?php echo $k; ?>" value="<?php echo $v; ?>" />
<?php
        }
    }
    /*
    // Example code for use with $this->do_hook
    function my_filter_function ($args,$obj)
    {
        $obj[0]->item = 'Post';
        $obj[0]->add = true;
        // args are an array (0=>$arg1,1=>$arg2)
        // may have more than one arg, dependant on filter
        return $args;
    }
    add_filter('wp_admin_ui_post_init','my_filter_function',10,2);
    // OR
    add_action('wp_admin_ui_post_init','my_filter_function',10,2);
    */
    function do_hook ()
    {
        $args = func_get_args();
        if(empty($args))
            return false;
        $filter = $args[0];
        unset($args[0]);
        $args = apply_filters('wp_admin_ui_'.$filter,$args,array(&$this,'wp_admin_ui_'.$filter));
        if(isset($args[1]))
            return $args[1];
        return false;
    }

    /**
     * @param array $array
     * @param array $allowed
     * @param string $url
     * @param bool $exclusive
     *
     * @return string
     */
    function var_update ($array=false,$allowed=false,$url=false,$exclusive=false)
    {
        $excluded = array('do','id','pg','search_query','order','order_dir','limit','action','exports_and_reports_download','exports_and_reports_export','export_type','export_delimiter','remove_export','updated','duplicate');
        if(false===$allowed)
            $allowed = array();
        if(!isset($_GET))
            $get = array();
        else
            $get = $_GET;
        if(is_array($array))
        {
            if(false===$exclusive)
            {
                foreach($excluded as $exclusion)
                    if(isset($get[$exclusion])&&!isset($array[$exclusion])&&!in_array($exclusion,$allowed))
                        unset($get[$exclusion]);
            }
            else
                $get = array();
            foreach($array as $key=>$val)
            {
                if(0<strlen($val))
                    $get[$key] = $val;
                elseif(isset($get[$key]))
                    unset($get[$key]);
            }
        }
        if(false===$url)
            $url = '';
        else
        {
            $url = explode('?',$_SERVER['REQUEST_URI']);
            $url = explode('#',$url[0]);
            $url = $url[0];
        }
        return $this->do_hook('var_update',$url.'?'.http_build_query($get),$array,$allowed,$url);
    }
    function sanitize ($input)
    {
        global $wpdb;
        $output = array();
        if (is_object($input)) {
            $input = (array) $input;
            foreach ($input as $key => $val) {
                $output[$key] = $this->sanitize($val);
            }
            $output = (object) $output;
        }
        elseif (is_array($input)) {
            foreach ($input as $key => $val) {
                $output[$key] = $this->sanitize($val);
            }
        }
        elseif (!empty($input))
            $output = $wpdb->_real_escape(trim($input));
        return $output;
    }
    function unsanitize ($input)
    {
        $output = array();
        if(is_object($input))
        {
            $input = (array) $input;
            foreach($input as $key => $val)
            {
                $output[$key] = $this->unsanitize($val);
            }
            $output = (object) $output;
        }
        elseif(is_array($input))
        {
            foreach($input as $key => $val)
            {
                $output[$key] = $this->unsanitize($val);
            }
        }
        elseif(empty($input))
        {
            $output = $input;
        }
        else
        {
            $output = stripslashes($input);
        }
        return $output;
    }
    function catch_columns ($full=false) {
        $data = $this->data;
        if ($full)
            $data = $this->full_data;
        if (!empty($data) && is_array($data) && isset($data[0]))
            $data = @current($data);
        $columns = array();
        foreach ((array) $data as $column => $value) {
            $column = trim(str_replace('`', '', $column));
            if (0 < strlen($column))
                $columns[] = $column;
        }
        $this->columns = $this->setup_columns($columns,'columns',true);
    }
    function setup_columns ($columns=null,$which='columns',$init=false)
    {
        if(null===$columns)
        {
            $columns = $this->$which;
            if($which=='columns')
                $init = true;
        }
        if(!empty($columns))
        {
            $new_columns = array();
            $filterable = false;
            if(empty($this->filters)&&(empty($this->search_columns)||$which=='search_columns')&&false!==$this->search)
            {
                $filterable = true;
                $this->filters = array();
            }
            foreach($columns as $column=>$attributes)
            {
                if(!is_array($attributes))
                    $column = $attributes;
                $attributes = $this->setup_column($column, $attributes, $filterable);
                if('search_columns' == $which && false === $attributes['search'])
                    continue;
                $new_columns[$column] = $attributes;
            }
            $columns = $new_columns;
        }
        if(false!==$init)
        {
            if(!empty($this->form_columns)&&($this->edit||$this->add||$this->duplicate))
                $this->form_columns = $this->setup_columns($this->form_columns,'form_columns');
            else
                $this->form_columns = $columns;
            if(!empty($this->view_columns)&&$this->view)
                $this->view_columns = $this->setup_columns($this->view_columns,'view_columns');
            else
                $this->view_columns = $this->form_columns;
            if(!empty($this->search_columns)&&$this->search)
                $this->search_columns = $this->setup_columns($this->search_columns,'search_columns');
            else
                $this->search_columns = $columns;
            if(!empty($this->export_columns)&&$this->export)
                $this->export_columns = $this->setup_columns($this->export_columns,'export_columns');
            else
                $this->export_columns = $columns;
            if(!empty($this->reorder_columns)&&$this->reorder)
                $this->reorder_columns = $this->setup_columns($this->reorder_columns,'reorder_columns');
            else
                $this->reorder_columns = $columns;
        }
        return $this->do_hook('setup_columns',$columns,$which,$init);
    }
    function setup_column ($column=null,$attributes=null,$filterable=false)
    {
        // Available Attributes
        // type = field type
            // type = date (data validation as date)
            // type = time (data validation as time)
            // type = datetime (data validation as datetime)
                // date_touch = use current timestamp when saving (even if readonly, if type is date-related)
                // date_touch_on_create = use current timestamp when saving ONLY on create (even if readonly, if type is date-related)
                // date_ongoing = use this additional column to search between as if the first is the "start" and the date_ongoing is the "end" for filter
            // type = text / other (single line text box)
            // type = desc (textarea)
            // type = number (data validation as int float)
            // type = decimal (data validation as decimal)
            // type = password (single line password box)
            // type = bool (single line password box)
            // type = related (select box)
                // related = table to relate to (if type=related) OR custom array of (key=>label or comma separated values) items
                // related_field = field name on table to show (if type=related) - default "name"
                // related_multiple = true (ability to select multiple values if type=related)
                // related_sql = custom where / order by SQL (if type=related)
        // readonly = true (shows as text)
        // display = false (doesn't show on form, but can be saved)
        // search = this field is searchable
        // filter = this field will be independantly searchable (by default, searchable fields are searched by the primary search box)
        // comments = comments to show for field
        // comments_top = true (shows comments above field instead of below)
        // real_name = the real name of the field (if using an alias for 'name')
        // group_related = true (uses HAVING instead of WHERE for filtering column)
        if(!is_array($attributes))
        {
            if (null !== $attributes)
                $column = $attributes;
            $attributes = array();
        }
        if(!isset($attributes['real_name']))
            $attributes['real_name'] = false;
        if(!isset($attributes['label']))
            $attributes['label'] = ucwords(str_replace('_',' ',$column));
        if(!isset($attributes['type']))
            $attributes['type'] = 'text';
        if('related'!=$attributes['type']||!isset($attributes['custom_relate']))
            $attributes['custom_relate'] = false;
        if('related'!=$attributes['type']||!isset($attributes['related']))
            $attributes['related'] = false;
        if('related'!=$attributes['type']||!isset($attributes['related_id']))
            $attributes['related_id'] = 'id';
        if('related'!=$attributes['type']||!isset($attributes['related_field']))
            $attributes['related_field'] = 'name';
        if('related'!=$attributes['type']||!isset($attributes['related_multiple']))
            $attributes['related_multiple'] = false;
        if('related'!=$attributes['type']||!isset($attributes['related_sql']))
            $attributes['related_sql'] = false;
        if('related'==$attributes['type']&&(is_array($attributes['related'])||strpos($attributes['related'],',')))
        {
            if(!is_array($attributes['related']))
            {
                $attributes['related'] = @explode(',',$attributes['related']);
                $related_items = array();
                foreach($attributes['related'] as $key=>$label)
                {
                    if(is_numeric($key))
                    {
                        $key = $label;
                        $label = ucwords(str_replace('_',' ',$label));
                    }
                    $related_items[$key] = $label;
                }
                $attributes['related'] = $related_items;
            }
            if(empty($attributes['related']))
                $attributes['related'] = false;
        }
        if(!isset($attributes['readonly']))
            $attributes['readonly'] = false;
        if(!isset($attributes['date_touch'])||!in_array($attributes['type'],array('date','time','datetime')))
            $attributes['date_touch'] = false;
        if(!isset($attributes['date_touch_on_create'])||!in_array($attributes['type'],array('date','time','datetime')))
            $attributes['date_touch_on_create'] = false;
        if(!isset($attributes['display']))
            $attributes['display'] = true;
        if(!isset($attributes['search'])||false===$this->search)
            $attributes['search'] =(false!==$this->search?true:false);
        if(!isset($attributes['filter'])||false===$this->search)
            $attributes['filter'] = false;
        if(false!==$attributes['filter']&&false!==$filterable)
            $this->filters[] = $column;
        if(false===$attributes['filter']||!isset($attributes['filter_label'])||!in_array($column,$this->filters))
            $attributes['filter_label'] = $attributes['label'];
        if(false===$attributes['filter']||!isset($attributes['filter_default'])||!in_array($column,$this->filters))
            $attributes['filter_default'] = false;
        if(false===$attributes['filter']||!isset($attributes['date_ongoing'])||!in_array($attributes['type'],array('date','time','datetime'))||!in_array($column,$this->filters))
            $attributes['date_ongoing'] = false;
        if(false===$attributes['filter']||!isset($attributes['date_ongoing'])||!in_array($attributes['type'],array('date','time','datetime'))||!isset($attributes['filter_ongoing_default'])||!in_array($column,$this->filters))
            $attributes['filter_ongoing_default'] = false;
        if(!isset($attributes['export']))
            $attributes['export'] = true;
        if(!isset($attributes['total_field']))
            $attributes['total_field'] = false;
        if(!isset($attributes['group_related']))
            $attributes['group_related'] = false;
        if(!isset($attributes['comments']))
            $attributes['comments'] = '';
        if(!isset($attributes['comments_top']))
            $attributes['comments_top'] = false;
        if(!isset($attributes['custom_input']))
            $attributes['custom_input'] = false;
        if(!isset($attributes['custom_display']))
            $attributes['custom_display'] = false;
        if(!isset($attributes['custom_form_display']))
            $attributes['custom_form_display'] = false;
        if(!isset($attributes['custom_view']))
            $attributes['custom_view'] = $attributes['custom_display'];
        return $this->do_hook('setup_column',$attributes,$column,$filterable);
    }
    function message ($msg)
    {
        $msg = $this->do_hook('message',$msg);
?>
	<div id="message" class="updated fade"><p><?php echo $msg; ?></p></div>
<?php
    }
    function error ($msg)
    {
        $msg = $this->do_hook('error',$msg);
?>
	<div id="message" class="error fade"><p><?php echo $msg; ?></p></div>
<?php
        return false;
    }
    function go ()
    {
        $this->do_hook('go');
        $_GET = $this->unsanitize($_GET);
        $_POST = $this->unsanitize($_POST);
        if(false!==$this->css)
        {
?>
    <link  type="text/css" rel="stylesheet" href="<?php echo esc_url( $this->css ); ?>" />
<?php
        }
        if(isset($this->custom[$this->action])&&function_exists("{$this->custom[$this->action]}"))
            call_user_func( $this->custom[$this->action], $this );
        elseif($this->action=='add'&&$this->add)
        {
            if($this->do=='create'&&$this->save&&!empty($_POST)&&!empty($_POST['_wpnonce'])&&false!==wp_verify_nonce($_POST['_wpnonce'],'wp-admin-ui-form-'.$this->do))
            {
                $this->save(1);
                if(false===$this->api)
                    $this->manage();
            }
            else
                $this->add();
        }
        elseif(($this->action=='edit'&&$this->edit)||($this->action=='duplicate'&&$this->duplicate))
        {
            if($this->do=='save'&&$this->save&&!empty($_POST)&&!empty($_POST['_wpnonce'])&&false!==wp_verify_nonce($_POST['_wpnonce'],'wp-admin-ui-form-'.$this->do))
            {
                $this->save();
            }
            $this->edit(($this->action=='duplicate'&&$this->duplicate?1:0));
        }
        elseif($this->action=='delete'&&$this->delete&&!empty($_GET['_wpnonce'])&&false!==wp_verify_nonce($_GET['_wpnonce'],'wp-admin-ui-'.$this->action))
        {
            $this->delete();
            if(false===$this->api)
                $this->manage();
        }
        elseif($this->action=='reorder'&&$this->reorder)
        {
            if(false===$this->table)
                return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "table" definition.');
            if(false===$this->identifier)
                return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "identifier" definition.');
            if($this->do=='save')
            {
                $this->reorder();
            }
            if(false===$this->api)
                $this->manage(1);
        }
        elseif($this->do=='save'&&$this->save&&!empty($_POST)&&!empty($_POST['_wpnonce'])&&false!==wp_verify_nonce($_POST['_wpnonce'],'wp-admin-ui-form-'.$this->do))
        {
            $this->save();
            if(false===$this->api)
                $this->manage();
        }
        elseif($this->do=='create'&&$this->save&&!empty($_POST)&&!empty($_POST['_wpnonce'])&&false!==wp_verify_nonce($_POST['_wpnonce'],'wp-admin-ui-form-'.$this->do))
        {
            $this->save(1);
            if(false===$this->api)
                $this->manage();
        }
        elseif($this->action=='view'&&$this->view)
            $this->view();
        elseif(false===$this->api)
            $this->manage();
    }
    function add ()
    {
        $this->do_hook('add');
?>
<div class="wrap">
    <div id="icon-edit-pages" class="icon32"<?php if(false!==$this->icon){ ?> style="background-position:0 0;background-image:url(<?php echo esc_url( $this->icon ); ?>);"<?php } ?>><br /></div>
    <h2><?php echo esc_html( $this->heading['add'] ); ?> <?php echo $this->item; ?> <small>(<a href="<?php echo esc_url( $this->var_update(array('action'=>'manage','id'=>'')) ); ?>">&laquo; Back to Manage</a>)</small></h2>
<?php $this->form(1); ?>
</div>
<?php
    }
    function edit ($duplicate=0)
    {
        if(!$this->duplicate)
            $duplicate = 0;
        $this->do_hook('edit',$duplicate);
        if(isset($this->custom['edit'])&&function_exists("{$this->custom['edit']}"))
            call_user_func( $this->custom['edit'], $this, $duplicate);
?>
<div class="wrap">
    <div id="icon-edit-pages" class="icon32"<?php if(false!==$this->icon){ ?> style="background-position:0 0;background-image:url(<?php echo esc_url( $this->icon ); ?>);"<?php } ?>><br /></div>
    <h2><?php echo esc_html($duplicate?$this->heading['duplicate']:$this->heading['edit']); ?> <?php echo $this->item; ?> <small>(<a href="<?php echo esc_url( $this->var_update(array('action'=>'manage','id'=>'')) ); ?>">&laquo; Back to Manage</a>)</small></h2>
<?php $this->form(0,$duplicate); ?>
</div>
<?php
    }
    function form ($create=0,$duplicate=0)
    {
        $this->do_hook('form',$create,$duplicate);
        if(isset($this->custom['form'])&&function_exists("{$this->custom['form']}"))
            return call_user_func( $this->custom['form'],$this,$create);
        if(false===$this->table&&false===$this->sql)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "table" definition.');
        global $wpdb;
        if(empty($this->form_columns))
            $this->form_columns = $this->columns;
        $submit = 'Add '.$this->item;
        $id = '';
        $vars = array('action'=>'manage','do'=>'create','id'=>'');
        if($create==0)
        {
            if(empty($this->row))
                $this->get_row();
            if(empty($this->row))
                return $this->error("<strong>Error:</strong> $this->item not found.");
            if($duplicate==0)
            {
                $submit = 'Save Changes';
                $id = $this->row[$this->identifier];
                $vars = array('action'=>'edit','do'=>'save','id'=>$id);
            }
        }
?>
    <form method="post" action="<?php echo esc_url( $this->var_update($vars) ); ?>" class="wp_admin_ui">
        <table class="form-table">
<?php
        foreach($this->form_columns as $column=>$attributes)
        {
            if(!is_array($attributes)) {
                $column = $attributes;
                $attributes = $this->setup_column($column);
            }
            if(!isset($this->row[$column]))
                $this->row[$column] = '';
            if($attributes['type']=='bool')
                $selected = ($this->row[$column]==1?' CHECKED':'');
            if(false===$attributes['display'])
                continue;
?>
    <tr valign="top">
        <th scope="row"><label for="admin_ui_<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $attributes['label'] ); ?></label></th>
        <td>
<?php
            if(!empty($attributes['comments'])&&!empty($attributes['comments_top']))
            {
?>
            <span class="description"><?php echo wp_kses_post( $attributes['comments'] ); ?></span>
<?php
                if($attributes['type']!='desc'||$attributes['type']!='code')
                    echo "<br />";
            }
            if(false!==$attributes['custom_input']&&function_exists("{$attributes['custom_input']}"))
            {
                $attributes['custom_input']($column,$attributes,$this);
?>
        </td>
    </tr>
<?php
                continue;
            }
            if(false!==$attributes['custom_form_display']&&function_exists("{$attributes['custom_form_display']}"))
            {
                $this->row[$column] = $attributes['custom_form_display']($column,$attributes,$this);
            }
            if(false!==$attributes['readonly'])
            {
?>
            <div id="admin_ui_<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $this->row[$column] ); ?></div>
<?php
            }
            else
            {
                if($attributes['type']=='bool')
                {
?>
            <input type="checkbox" name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" value="1"<?php echo $selected; ?> />
<?php
                }
                elseif($attributes['type']=='password')
                {
?>
            <input type="password" name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" value="<?php echo esc_attr( $this->row[$column] ); ?>" class="regular-text" />
<?php
                }
                elseif($attributes['type']=='desc'||$attributes['type']=='code')
                {
?>
            <textarea name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" rows="10" cols="50"><?php echo esc_textarea( $this->row[$column] ); ?></textarea>
<?php
                }
                elseif($attributes['type']=='related'&&false!==$attributes['related'])
                {
                    if(!is_array($attributes['related']))
                    {
                        $related = $wpdb->get_results('SELECT id,`'.$this->sanitize( (string) $attributes['related_field'] ).'` FROM '.(string) $attributes['related'].(!empty($attributes['related_sql'])?' '.(string) $attributes['related_sql']:''));
?>
            <select name="<?php echo esc_attr( $column ); ?><?php echo (false!==$attributes['related_multiple']?'[]':''); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>"<?php echo (false!==$attributes['related_multiple']?' size="10" style="height:auto;" MULTIPLE':''); ?>>
<?php
                        $selected_options = explode(',',$this->row[$column]);
                        foreach($related as $option)
                        {
?>
                <option value="<?php echo esc_attr( $option->id ); ?>"<?php echo (in_array($option->id,$selected_options)?' SELECTED':''); ?>><?php echo esc_html( $option->$attributes['related_field'] ); ?></option>
<?php
                        }
?>
            </select>
<?php
                    }
                    else
                    {
                        $related = $attributes['related'];
?>
            <select name="<?php echo esc_attr( $column ); ?><?php echo (false!==$attributes['related_multiple']?'[]':''); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>"<?php echo (false!==$attributes['related_multiple']?' size="10" style="height:auto;" MULTIPLE':''); ?>>
<?php
                        $selected_options = explode(',',$this->row[$column]);
                        foreach($related as $option_id=>$option)
                        {
?>
                <option value="<?php echo esc_attr( $option_id ); ?>"<?php echo (in_array($option_id,$selected_options)?' SELECTED':''); ?>><?php echo esc_html( $option ); ?></option>
<?php
                        }
?>
            </select>
<?php
                    }
                }
                else
                {
?>
            <input type="text" name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" value="<?php echo esc_attr( $this->row[$column] ); ?>" class="regular-text" />
<?php
                }
            }
            if(!empty($attributes['comments'])&&false===$attributes['comments_top'])
            {
                if($attributes['type']!='desc'||$attributes['type']!='code')
                    echo "<br />";
?>
            <span class="description"><?php echo wp_kses_post( $attributes['comments'] ); ?></span>
<?php
            }
?>
        </td>
    </tr>
<?php
        }
?>
        </table>
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php echo esc_attr( $submit ); ?>" />
            <?php wp_nonce_field( 'wp-admin-ui-form-' . $vars['do'] ); ?>
        </p>
    </form>
<?php
    }
    function view ()
    {
        $this->do_hook('view');
        if(isset($this->custom['view'])&&function_exists("{$this->custom['view']}"))
            return call_user_func($this->custom['view'],$this);
        if(false===$this->table&&false===$this->sql)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "table" definition.');
        global $wpdb;
        if(empty($this->row))
            $this->get_row();
        if(empty($this->row))
            return $this->error("<strong>Error:</strong> $this->item not found.");
?>
<div class="wrap">
    <div id="icon-edit-pages" class="icon32"<?php if(false!==$this->icon){ ?> style="background-position:0 0;background-image:url(<?php echo esc_url( $this->icon ); ?>);"<?php } ?>><br /></div>
    <h2><?php echo esc_html( $this->heading['view'] ); ?> <?php echo esc_html( $this->item ); ?> <small>(<a href="<?php echo esc_url( $this->var_update(array('action'=>'manage','id'=>'')) ); ?>">&laquo; Back to Manage</a>)</small></h2>
    <table class="form-table">
<?php
        foreach($this->view_columns as $column=>$attributes)
        {
            if(!is_array($attributes)) {
                $column = $attributes;
                $attributes = $this->setup_column($column);
            }
            if(!isset($this->row[$column]))
                $this->row[$column] = '';
            if(false===$attributes['display'])
                continue;
?>
        <tr valign="top">
            <th scope="row"><label for="admin_ui_<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $attributes['label'] ); ?></label></th>
            <td>
<?php
            if(false!==$attributes['custom_view']&&function_exists("{$attributes['custom_view']}"))
            {
                echo $attributes['custom_view']($this->row[$column],$this->row,$column,$attributes,$this);
?>
            </td>
        </tr>
<?php
                continue;
            }
            if('date'==$attributes['type'])
                $this->row[$column] = date_i18n('Y/m/d',strtotime($this->row[$column]));
            elseif('time'==$attributes['type'])
                $this->row[$column] = date_i18n('g:i:s A',strtotime($this->row[$column]));
            elseif('datetime'==$attributes['type'])
                $this->row[$column] = date_i18n('Y/m/d g:i:s A',strtotime($this->row[$column]));
            elseif($attributes['type']=='bool')
                $this->row[$column] = ($this->row[$column]==1?'Yes':'No');
            elseif($attributes['type']=='number')
                $this->row[$column] = (int) ($this->row[$column]);
            elseif($attributes['type']=='decimal')
                $this->row[$column] = number_format($this->row[$column],2);
            elseif($attributes['type']=='related'&&false!==$attributes['related'])
            {
                $old_value = $this->row[$column];
                $this->row[$column] = '';
                if(!empty($old_value))
                {
                    $this->row[$column] = array();
                    if(!is_array($attributes['related']))
                    {
                        $related = $wpdb->get_results('SELECT `id`,`'.$this->sanitize( (string) $attributes['related_field'] ).'` FROM '.(string) $attributes['related'].' WHERE `id` IN ('.$this->sanitize( (string) $old_value ).')'.(!empty($attributes['related_sql'])?' '.(string) $attributes['related_sql']:''));
                        foreach($related as $option)
                        {
                            $this->row[$column][] = esc_html( $option->$attributes['related_field'] );
                        }
                    }
                    else
                    {
                        $related = $attributes['related'];
                        $selected_options = explode(',',$old_value);
                        foreach($related as $option_id=>$option)
                        {
                            if(in_array($option_id,$selected_options))
                            {
                                $this->row[$column][] = esc_html( $option );
                            }
                        }
                    }
                    $this->row[$column] = '<ul><li>'.implode('</li><li>',$this->row[$column]).'</li></ul>';
                }
                else
                {
                    $this->row[$column] = 'N/A';
                }
            }
?>
            <div id="admin_ui_<?php echo esc_attr( $column ); ?>"><?php echo wp_kses_post( $this->row[$column] ); ?></div>
<?php
            if(!empty($attributes['comments'])&&false===$attributes['comments_top'])
            {
?>
            <span class="description"><?php echo wp_kses_post( $attributes['comments'] ); ?></span>
<?php
            }
?>
            </td>
        </tr>
<?php
        }
?>
    </table>
</div>
<?php
    }
    function delete ($id=false)
    {
        $this->do_hook('pre_delete',$id);
        if(isset($this->custom['delete'])&&function_exists("{$this->custom['delete']}"))
            return call_user_func( $this->custom['delete'],$this);
        if(false===$this->table&&false===$this->sql)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "table" definition.');
        if(false===$this->id&&false===$id)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "id" definition.');
        if(false===$id)
            $id = $this->id;
        global $wpdb;
        $check = $wpdb->query($wpdb->prepare("DELETE FROM $this->table WHERE `id`=%d",array($id)));
        if($check)
            $this->message("<strong>Deleted:</strong> $this->item has been deleted.");
        else
            $this->error("<strong>Error:</strong> $this->item has not been deleted.");
        $this->do_hook('post_delete',$id);
    }
    function save ($create=0)
    {
        $this->do_hook('pre_save',$create);
        if(isset($this->custom['save'])&&function_exists("{$this->custom['save']}"))
            return call_user_func( $this->custom['save'],$this,$create);
        global $wpdb;
        $action = 'saved';
        if($create==1)
            $action = 'created';
        $column_sql = array();
        $values = array();
        $data = array();
        foreach($this->form_columns as $column=>$attributes)
        {
            if(!is_array($attributes)) {
                $column = $attributes;
                $attributes = $this->setup_column($column);
            }
            $vartype = '%s';
            if(false===$attributes['display']||false!==$attributes['readonly'])
            {
                if(!in_array($attributes['type'],array('date','time','datetime')))
                    continue;
                if(false===$attributes['date_touch']&&(false===$attributes['date_touch_on_create']||$create!=1||$this->id>0))
                    continue;
            }
            if(in_array($attributes['type'],array('date','time','datetime')))
            {
                $format = "Y-m-d H:i:s";
                if($attributes['type']=='date')
                    $format = "Y-m-d";
                if($attributes['type']=='time')
                    $format = "H:i:s";
                if(false!==$attributes['date_touch']||(false!==$attributes['date_touch_on_create']&&$create==1&&$this->id<1))
                    $value = date_i18n($format);
                else
                {
                    $value = date_i18n($format,strtotime(($attributes['type']=='time'?date_i18n('Y-m-d '):'').$_POST[$column]));
                }
            }
            else
            {
                if (!isset($_POST[$column]))
                    $_POST[$column] = '';
                if($attributes['type']=='bool')
                {
                    $vartype = '%d';
                    $value = 0;
                    if(isset($_POST[$column]) && $_POST[$column] == 1)
                        $value = 1;
                }
                elseif($attributes['type']=='number')
                {
                    $vartype = '%d';
                    $value = number_format($_POST[$column],0,'','');
                }
                elseif($attributes['type']=='decimal')
                {
                    $vartype = '%d';
                    $value = number_format($_POST[$column],2,'.','');
                }
                elseif($attributes['type']=='related')
                {
                    if(is_array($_POST[$column]))
                        $value = implode(',',$_POST[$column]);
                    else
                        $value = $_POST[$column];
                }
                else
                    $value = $_POST[$column];
            }
            if(isset($attributes['custom_save'])&&false!==$attributes['custom_save']&&function_exists("{$attributes['custom_save']}"))
                $value = $attributes['custom_save']($value,$column,$attributes,$this);
            $column_sql[] = "`" . $this->sanitize( $column ) . "`=$vartype";
            $values[] = $value;
            $data[$column] = $value;
        }
        $column_sql = implode(',',$column_sql);
        if($create==0&&$this->id>0)
        {
            $this->insert_id = $this->id;
            $values[] = $this->id;
            $check = $wpdb->query($wpdb->prepare("UPDATE $this->table SET $column_sql WHERE id=%d",$values));
        }
        else
            $check = $wpdb->query($wpdb->prepare("INSERT INTO $this->table SET $column_sql",$values));
        if($check)
        {
            if($this->insert_id==0)
                $this->insert_id = $wpdb->insert_id;
            $this->message('<strong>Success!</strong> '.$this->item.' '.$action.' successfully.');
        }
        else
            $this->error('<strong>Error:</strong> '.$this->item.' has not been '.$action.'.');
        $this->do_hook('post_save',$this->insert_id,$data,$create);
    }
    function reorder ()
    {
        $this->do_hook('pre_reorder');
        if(isset($this->custom['reorder'])&&function_exists("{$this->custom['reorder']}"))
            return call_user_func( $this->custom['reorder'],$this);
        global $wpdb;
        if(false===$this->table)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "table" definition.');
        if(false===$this->identifier)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "identifier" definition.');
        if(isset($_POST['order'])&&!empty($_POST['order']))
        {
            foreach($_POST['order'] as $order => $id)
                $updated = $wpdb->update($this->table,array($this->reorder=>$order),array($this->identifier=>$id),array('%s','%d'),array('%d'));
            $this->message('<strong>Success!</strong> Order updated successfully.');
        }
        else
            $this->error('<strong>Error:</strong> Order has not been updated.');
        $this->do_hook('post_reorder',$this->insert_id);
    }
    function field_value ($value,$field_name,$attributes)
    {
        global $wpdb;
        if('date'==$attributes['type']) {
            if ('N/A' == $value || '0000-00-00' == $value || '0000-00-00 00:00:00' == $value)
                $value = 'N/A';
            else
                $value = date_i18n('Y/m/d',strtotime($value));
        }
        elseif('time'==$attributes['type']) {
            if ('N/A' == $value || '00:00:00' == $value || '0000-00-00 00:00:00' == $value)
                $value = 'N/A';
            else
                $value = date_i18n('g:i:s A',strtotime($value));
        }
        elseif('datetime'==$attributes['type']) {
            if ('N/A' == $value || '0000-00-00' == $value || '0000-00-00 00:00:00' == $value)
                $value = 'N/A';
            else
                $value = date_i18n('Y/m/d g:i:s A',strtotime($value));
        }
        elseif('related'==$attributes['type']&&false!==$attributes['related'])
        {
            $column_data = array();
            if(!is_array($attributes['related']))
            {
                $selected_options = explode(',', trim($value));
                if (!empty($this->related) && isset($this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']]) && !empty($this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']])) {
                    foreach ($selected_options as $option) {
                        if ($attributes['related_id'] == $attributes['related_field']) {
                            if (isset($this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']][$option]))
                                $column_data[$option] = $option;
                        }
                        elseif (in_array($option, $this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']]))
                            $column_data[$option] = $this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']][$option];
                    }
                }
                if (empty($column_data) && !empty($selected_options)) {
                    $limited = " WHERE `" . $this->sanitize( $attributes['related_id'] ) . "` IN ('" . implode("', '", $this->sanitize( $selected_options )) . "')";
                    $related = $wpdb->get_results('SELECT `'.$this->sanitize( $attributes['related_id'] ).'`,`'.$attributes['related_field'].'` FROM '.$attributes['related'].(!empty($attributes['related_sql'])?' '.$attributes['related_sql']:$limited));
                    foreach($related as $option)
                        if(in_array($option->{$attributes['related_id']}, $selected_options)) {
                            $column_data[$option->{$attributes['related_id']}] = $option->{$attributes['related_field']};
                            if (!isset($this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']]))
                                $this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']] = array();
                            $this->related[$attributes['related'] . '_' . $attributes['related_field'] . '_' . $attributes['related_id']][$option->{$attributes['related_id']}] = $option->{$attributes['related_field']};
                        }
                }
            }
            else
            {
                $related = $attributes['related'];
                $selected_options = explode(',',$value);
                foreach($related as $option_id=>$option)
                    if(in_array($option_id,$selected_options))
                        $column_data[$option_id] = $option;
            }
            $value = implode(', ',$column_data);
        }
        elseif($attributes['type']=='bool')
            $value = ($value==1?'Yes':'No');
        elseif($attributes['type']=='number')
            $value = number_format($value, 0);
        elseif($attributes['type']=='decimal')
            $value = number_format($value, 2);
        if(false!==$attributes['custom_relate'])
        {
            $table = $attributes['custom_relate'];
            $on = $this->sanitize( $field_name );
            $is = $this->sanitize( $value );
            $what = array('name');
            if(is_array($table))
            {
                if(isset($table['on']))
                    $on = $this->sanitize($table['on']);
                if(isset($table['is'])&&isset($row[$table['is']]))
                    $is = $this->sanitize($row[$table['is']]);
                if(isset($table['what']))
                {
                    $what = array();
                    if(is_array($table['what']))
                    {
                        foreach($table['what'] as $wha)
                            $what[] = $this->sanitize($wha);
                    }
                    else
                        $what[] = $this->sanitize($table['what']);
                }
                if(isset($table['table']))
                    $table = $table['table'];
            }
            $table = $this->sanitize($table);
            $wha = implode(',',$what);
            $sql = "SELECT $wha FROM $table WHERE `$on`='$is'";
            $results = @current($wpdb->get_results($sql,ARRAY_A));
            if(!empty($results))
            {
                $val = array();
                foreach($what as $wha)
                {
                    if(isset($results[$wha]))
                        $val[] = $results[$wha];
                }
                if(!empty($val))
                    $value = implode(' ',$val);
            }
        }
        return $value;
    }
    function export ()
    {
        $this->do_hook('pre_export');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $url = explode('/',$_SERVER['REQUEST_URI']);
        $url = array_reverse($url);
        $url = $url[0];
        if(false===($credentials=request_filesystem_credentials($url,'',false,ABSPATH)))
        {
            $this->error("<strong>Error:</strong> Your hosting configuration does not allow access to add files to your site.");
            return false;
        }
        if(!WP_Filesystem($credentials,ABSPATH))
        {
            request_filesystem_credentials($url,'',true,ABSPATH); //Failed to connect, Error and request again
            $this->error("<strong>Error:</strong> Your hosting configuration does not allow access to add files to your site.");
            return false;
        }
        global $wp_filesystem;
        if(isset($this->custom['export'])&&function_exists("{$this->custom['export']}"))
            return call_user_func( $this->custom['export'],$this);
        if(empty($this->full_data))
            $this->get_data(true);
        $dir = dirname(WP_ADMIN_UI_EXPORT_DIR);
        if(!file_exists(WP_ADMIN_UI_EXPORT_DIR))
        {
            if(!$wp_filesystem->is_writable($dir)||!($dir = $wp_filesystem->mkdir(WP_ADMIN_UI_EXPORT_DIR)))
            {
                $this->error("<strong>Error:</strong> Your export directory (<strong>" . WP_ADMIN_UI_EXPORT_DIR . "</strong>) did not exist and couldn&#8217;t be created by the web server. Check the directory permissions and try again.");
                return false;
            }
        }
        if(!$wp_filesystem->is_writable(WP_ADMIN_UI_EXPORT_DIR))
        {
            $this->error("<strong>Error:</strong> Your export directory (<strong>" . WP_ADMIN_UI_EXPORT_DIR . "</strong>) needs to be writable for this plugin to work. Double-check it and try again.");
            return false;
        }
        if(isset($_GET['remove_export']))
        {
            $this->do_hook('pre_remove_export',WP_ADMIN_UI_EXPORT_DIR.'/'.str_replace('/','',$_GET['remove_export']));
            if($wp_filesystem->exists(WP_ADMIN_UI_EXPORT_DIR.'/'.str_replace('/','',$_GET['remove_export'])))
            {
                $remove = @unlink(WP_ADMIN_UI_EXPORT_DIR.'/'.str_replace('/','',$_GET['remove_export']));
                if($remove)
                {
                    $this->do_hook('post_remove_export',$_GET['remove_export'],true);
                    $this->message('<strong>Success:</strong> Export removed successfully.');
                    return;
                }
                else
                {
                    $this->do_hook('post_remove_export',$_GET['remove_export'],false);
                    $this->error("<strong>Error:</strong> Your export directory (<strong>" . WP_ADMIN_UI_EXPORT_DIR . "</strong>) needs to be writable for this plugin to work. Double-check it and try again.");
                    return false;
                }
            }
            else
            {
                $this->error("<strong>Error:</strong> That file does not exist in the export directory.");
                return false;
            }
        }
        else
        {
            if($this->export_type=='csv')
            {
                $export_file = str_replace('-','_',sanitize_title($this->items)).'_'.date_i18n('m-d-Y_H-i-s').'_'.wp_generate_password(5,false).'.csv';
	            $export_file = apply_filters( 'wp_admin_ui_export_file', $export_file, 'csv', $this->export_type, $this->items, $this );
                $fp = fopen(WP_ADMIN_UI_EXPORT_DIR.'/'.$export_file,'a+');
                $head = array();
                $first = true;
                foreach($this->export_columns as $key=>$attributes)
                {
                    if(!is_array($attributes)) {
                        $key = $attributes;
                        $attributes = $this->setup_column($key);
                    }
                    if(false===$attributes['display']&&false===$attributes['export'])
                        continue;
                    if($first)
                    {
                        $attributes['label'] .= ' ';
                        $first = false;
                    }
                    $head[] = $attributes['label'];
                }
                fputcsv($fp,$head,",");
                foreach($this->full_data as $item)
                {
                    $line = array();
                    foreach($this->export_columns as $key=>$attributes)
                    {
                        if(!is_array($attributes)) {
                            $key = $attributes;
                            $attributes = $this->setup_column($key);
                        }
                        if(false===$attributes['display']&&false===$attributes['export'])
                            continue;
                        $item[$key] = $this->field_value($item[$key],$key,$attributes);
                        if(false!==$attributes['custom_display']&&function_exists("{$attributes['custom_display']}"))
                            $item[$key] = $attributes['custom_display']($item[$key],$item,$key,$attributes,$this);
                        $line[] = str_replace(array("\r","\n"),' ',$item[$key]);
                    }
                    fputcsv($fp,$line,",");
                }
                fclose($fp);
                $this->message('<strong>Success:</strong> Your export is ready, the download should begin in a few moments. If it doesn\'t, <a href="'.$this->export_url.urlencode($export_file).'">click here to access your CSV export file</a>.<br /><br />When you are done with your export, <a href="'.$this->var_update(array('remove_export'=>urlencode($export_file),'action'=>'export')).'">click here to remove it</a>, otherwise the export will be deleted within 24 hours of generation.');
                echo '<script type="text/javascript">window.open("'.$this->export_url.urlencode($export_file).'");</script>';
            }
            elseif($this->export_type=='tsv')
            {
                $export_file = str_replace('-','_',sanitize_title($this->items)).'_'.date_i18n('m-d-Y_H-i-s').'_'.wp_generate_password(5,false).'.tsv';
	            $export_file = apply_filters( 'wp_admin_ui_export_file', $export_file, 'tsv', $this->export_type, $this->items, $this );
                $fp = fopen(WP_ADMIN_UI_EXPORT_DIR.'/'.$export_file,'a+');
                $head = array();
                $first = true;
                foreach($this->export_columns as $key=>$attributes)
                {
                    if(!is_array($attributes)) {
                        $key = $attributes;
                        $attributes = $this->setup_column($key);
                    }
                    if(false===$attributes['display']&&false===$attributes['export'])
                        continue;
                    if($first)
                    {
                        $attributes['label'] .= ' ';
                        $first = false;
                    }
                    $head[] = $attributes['label'];
                }
                fputcsv($fp,$head,"\t");
                foreach($this->full_data as $item)
                {
                    $line = array();
                    foreach($this->export_columns as $key=>$attributes)
                    {
                        if(!is_array($attributes)) {
                            $key = $attributes;
                            $attributes = $this->setup_column($key);
                        }
                        if(false===$attributes['display']&&false===$attributes['export'])
                            continue;
                        $item[$key] = $this->field_value($item[$key],$key,$attributes);
                        if(false!==$attributes['custom_display']&&function_exists("{$attributes['custom_display']}"))
                            $item[$key] = $attributes['custom_display']($item[$key],$item,$key,$attributes,$this);
                        $line[] = str_replace(array("\r","\n"),' ',$item[$key]);
                    }
                    fputcsv($fp,$line,"\t");
                }
                fclose($fp);
                $this->message('<strong>Success:</strong> Your export is ready, the download should begin in a few moments. If it doesn\'t, <a href="'.$this->export_url.urlencode($export_file).'">click here to access your TSV export file</a>.<br /><br />When you are done with your export, <a href="'.$this->var_update(array('remove_export'=>urlencode($export_file),'action'=>'export')).'">click here to remove it</a>, otherwise the export will be deleted within 24 hours of generation.');
                echo '<script type="text/javascript">window.open("'.$this->export_url.urlencode($export_file).'");</script>';
            }
            elseif($this->export_type=='pipe')
            {
                $export_file = str_replace('-','_',sanitize_title($this->items)).'_'.date_i18n('m-d-Y_H-i-s').'_'.wp_generate_password(5,false).'.txt';
	            $export_file = apply_filters( 'wp_admin_ui_export_file', $export_file, 'txt', $this->export_type, $this->items, $this );
                $fp = fopen(WP_ADMIN_UI_EXPORT_DIR.'/'.$export_file,'a+');
                $head = array();
                $first = true;
                foreach($this->export_columns as $key=>$attributes)
                {
                    if(!is_array($attributes)) {
                        $key = $attributes;
                        $attributes = $this->setup_column($key);
                    }
                    if(false===$attributes['display']&&false===$attributes['export'])
                        continue;
                    if($first)
                    {
                        $attributes['label'] .= ' ';
                        $first = false;
                    }
                    $head[] = $attributes['label'];
                }
                fputcsv($fp,$head,"|");
                foreach($this->full_data as $item)
                {
                    $line = array();
                    foreach($this->export_columns as $key=>$attributes)
                    {
                        if(!is_array($attributes)) {
                            $key = $attributes;
                            $attributes = $this->setup_column($key);
                        }
                        if(false===$attributes['display']&&false===$attributes['export'])
                            continue;
                        $item[$key] = $this->field_value($item[$key],$key,$attributes);
                        if(false!==$attributes['custom_display']&&function_exists("{$attributes['custom_display']}"))
                            $item[$key] = $attributes['custom_display']($item[$key],$item,$key,$attributes,$this);
                        $line[] = str_replace(array("\r","\n"),' ',$item[$key]);
                    }
                    fputcsv($fp,$line,"|");
                }
                fclose($fp);
                $this->message('<strong>Success:</strong> Your export is ready, the download should begin in a few moments. If it doesn\'t, <a href="'.$this->export_url.urlencode($export_file).'">click here to access your TXT export file</a>.<br /><br />When you are done with your export, <a href="'.$this->var_update(array('remove_export'=>urlencode($export_file),'action'=>'export')).'">click here to remove it</a>, otherwise the export will be deleted within 24 hours of generation.');
                echo '<script type="text/javascript">window.open("'.$this->export_url.urlencode($export_file).'");</script>';
            }
            elseif($this->export_type=='custom')
            {
                $export_file = str_replace('-','_',sanitize_title($this->items)).'_'.date_i18n('m-d-Y_H-i-s').'_'.wp_generate_password(5,false).'.txt';
	            $export_file = apply_filters( 'wp_admin_ui_export_file', $export_file, 'txt', $this->export_type, $this->items, $this );
                $fp = fopen(WP_ADMIN_UI_EXPORT_DIR.'/'.$export_file,'a+');
                $head = array();
                $first = true;
                foreach($this->export_columns as $key=>$attributes)
                {
                    if(!is_array($attributes)) {
                        $key = $attributes;
                        $attributes = $this->setup_column($key);
                    }
                    if(false===$attributes['display']&&false===$attributes['export'])
                        continue;
                    if($first)
                    {
                        $attributes['label'] .= ' ';
                        $first = false;
                    }
                    $head[] = $attributes['label'];
                }
                fputcsv($fp,$head,"$this->export_delimiter");
                foreach($this->full_data as $item)
                {
                    $line = array();
                    foreach($this->export_columns as $key=>$attributes)
                    {
                        if(!is_array($attributes)) {
                            $key = $attributes;
                            $attributes = $this->setup_column($key);
                        }
                        if(false===$attributes['display']&&false===$attributes['export'])
                            continue;
                        $item[$key] = $this->field_value($item[$key],$key,$attributes);
                        if(false!==$attributes['custom_display']&&function_exists("{$attributes['custom_display']}"))
                            $item[$key] = $attributes['custom_display']($item[$key],$item,$key,$attributes,$this);
                        $line[] = str_replace(array("\r","\n"),' ',$item[$key]);
                    }
                    fputcsv($fp,$line,"$this->export_delimiter");
                }
                fclose($fp);
                $this->message('<strong>Success:</strong> Your export is ready, the download should begin in a few moments. If it doesn\'t, <a href="'.$this->export_url.urlencode($export_file).'">click here to access your TXT export file</a>.<br /><br />When you are done with your export, <a href="'.$this->var_update(array('remove_export'=>urlencode($export_file),'action'=>'export')).'">click here to remove it</a>, otherwise the export will be deleted within 24 hours of generation.');
                echo '<script type="text/javascript">window.open("'.$this->export_url.urlencode($export_file).'");</script>';
            }
            elseif($this->export_type=='xml')
            {
                $export_file = str_replace('-','_',sanitize_title($this->items)).'_'.date_i18n('m-d-Y_H-i-s').'_'.wp_generate_password(5,false).'.xml';
	            $export_file = apply_filters( 'wp_admin_ui_export_file', $export_file, 'xml', $this->export_type, $this->items, $this );
                $fp = fopen(WP_ADMIN_UI_EXPORT_DIR.'/'.$export_file,'a+');
                $head = '<'.'?'.'xml version="1.0" encoding="'.get_bloginfo('charset').'" '.'?'.'>'."\r\n<items count=\"".count($this->full_data)."\">\r\n";
                $head = substr($head,0,-1);
                fwrite($fp,$head);
                foreach($this->full_data as $item)
                {
                    $line = "\t<item>\r\n";
                    foreach($this->export_columns as $key=>$attributes)
                    {
                        if(!is_array($attributes)) {
                            $key = $attributes;
                            $attributes = $this->setup_column($key);
                        }
                        if(false===$attributes['display']&&false===$attributes['export'])
                            continue;
                        $item[$key] = $this->field_value($item[$key],$key,$attributes);
                        if(false!==$attributes['custom_display']&&function_exists("{$attributes['custom_display']}"))
                            $item[$key] = $attributes['custom_display']($item[$key],$item,$key,$attributes,$this);
                        $line .= "\t\t<{$key}><![CDATA[".$item[$key]."]]></{$key}>\r\n";
                    }
                    $line .= "\t</item>\r\n";
                    fwrite($fp,$line);
                }
                $foot = '</items>' ;
                fwrite($fp,$foot);
                fclose($fp);
                $this->message('<strong>Success:</strong> Your export is ready, the download should begin in a few moments. If it doesn\'t, <a href="'.$this->export_url.urlencode($export_file).'">click here to download your XML export file</a>.<br /><br />When you are done with your export, <a href="'.$this->var_update(array('remove_export'=>urlencode($export_file),'action'=>'export')).'">click here to remove it</a>, otherwise the export will be deleted within 24 hours of generation.');
                echo '<script type="text/javascript">window.open("'.$this->export_url.urlencode($export_file).'");</script>';
            }
            elseif($this->export_type=='json')
            {
                $export_file = str_replace('-','_',sanitize_title($this->items)).'_'.date_i18n('m-d-Y_H-i-s').'_'.wp_generate_password(5,false).'.json';
	            $export_file = apply_filters( 'wp_admin_ui_export_file', $export_file, 'json', $this->export_type, $this->items, $this );
                $fp = fopen(WP_ADMIN_UI_EXPORT_DIR.'/'.$export_file,'a+');
                $data = array('items'=>array('count'=>count($this->full_data),'item'=>array()));
                foreach($this->full_data as $item)
                {
                    $row = array();
                    foreach($this->export_columns as $key=>$attributes)
                    {
                        if(!is_array($attributes)) {
                            $key = $attributes;
                            $attributes = $this->setup_column($key);
                        }
                        if(false===$attributes['display']&&false===$attributes['export'])
                            continue;
                        $item[$key] = $this->field_value($item[$key],$key,$attributes);
                        if(false!==$attributes['custom_display']&&function_exists("{$attributes['custom_display']}"))
                            $item[$key] = $attributes['custom_display']($item[$key],$item,$key,$attributes,$this);
                        $row[$key] = $item[$key];
                    }
                    $data['items']['item'][] = $row;
                }
                fwrite($fp,json_encode($data));
                fclose($fp);
                $this->message('<strong>Success:</strong> Your export is ready, the download should begin in a few moments. If it doesn\'t, <a href="'.$this->export_url.urlencode($export_file).'">click here to access your JSON export file</a>.<br /><br />When you are done with your export, <a href="'.$this->var_update(array('remove_export'=>urlencode($export_file),'action'=>'export')).'">click here to remove it</a>, otherwise the export will be deleted within 24 hours of generation.');
                echo '<script type="text/javascript">window.open("'.$this->export_url.urlencode($export_file).'");</script>';
            }
            else
            {
                $this->error("<strong>Error:</strong> Invalid export type.");
                return false;
            }
        }

	    $this->exported_file = $export_file;

        $this->do_hook('post_export',$export_file);
    }
    function get_row ($id=false)
    {
        if(isset($this->custom['row'])&&function_exists("{$this->custom['row']}"))
            return call_user_func( $this->custom['row'],$this);
        if(false===$this->table&&false===$this->sql)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "table" definition.');
        if(false===$this->id&&false===$id)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "id" definition.');
        if(false===$id)
            $id = $this->id;
        global $wpdb;
        $sql = "SELECT * FROM $this->table WHERE `id`=".$this->sanitize($id);
        $row = @current($wpdb->get_results($sql,ARRAY_A));
        $row = $this->do_hook('get_row',$row,$id);
        if(!empty($row))
            $this->row = $row;

	    return $row;
    }
    function get_data ($full=false)
    {
        if(isset($this->custom['data'])&&function_exists("{$this->custom['data']}"))
            return call_user_func( $this->custom['data'],$this);
        if(false===$this->table&&false===$this->sql)
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "table" definition.');
        global $wpdb;
        $this->sql_count = trim($this->sql_count);
        if (empty($this->sql_count))
            $this->sql_count = false;
        if(false===$this->sql)
        {
            $calc_found_sql = 'SQL_CALC_FOUND_ROWS';
            if ($full || false !== $this->sql_count)
                $calc_found_sql = '';
            $totals = '';
            foreach($this->columns as $key=>$column)
            {
                $attributes = $column;
                if(!is_array($attributes))
                    $attributes = $this->setup_column($column);
                if(true!==$attributes['total_field'])
                    continue;
                if(is_array($column))
                    $column = $key;
                if (!is_array($totals))
                    $totals = array();
                $columnfield = '`'.$column.'`';
                if($attributes['real_name']!==false)
                    $columnfield = $attributes['real_name'];
                $totals[] = 'SUM('.$columnfield.') AS `wp_admin_ui_total_'.$this->sanitize($column).'`';
            }
            if(is_array($totals))
                $calc_found_sql .= ' '.implode(',',$totals).', ';
            $this->sql = "SELECT {$calc_found_sql} * FROM $this->table";
        }
        $sql = ' '.str_replace(array("\n","\r",'  '),' ',' '.$this->sql).' ';
        $calc_found_sql = 'SQL_CALC_FOUND_ROWS';
        if ($full || false !== $this->sql_count)
            $calc_found_sql = '';
        $totals = '';
        foreach($this->columns as $key=>$column)
        {
            $attributes = $column;
            if(!is_array($attributes))
                $attributes = $this->setup_column($column);
            if(true!==$attributes['total_field'])
                continue;
            if(is_array($column))
                $column = $key;
            if (!is_array($totals))
                $totals = array();
            $columnfield = '`'.$column.'`';
            if($attributes['real_name']!==false)
                $columnfield = $attributes['real_name'];
            $totals[] = 'SUM('.$columnfield.') AS `wp_admin_ui_total_'.$this->sanitize($column).'`';
        }
        if(is_array($totals))
            $calc_found_sql .= ' '.implode(',',$totals).', ';
        $sql = preg_replace('/ SELECT /i'," SELECT {$calc_found_sql} ", preg_replace('/ SELECT SQL_CALC_FOUND_ROWS /i',' SELECT ',$sql, 1), 1);
        $wheresql = $havingsql = $ordersql = $limitsql = '';
        $other_sql = $having_sql = array();
        if ($full || false !== $this->sql_count)
            preg_match('/SELECT (.*) FROM/i',$sql,$selectmatches);
        else
            preg_match('/SELECT SQL_CALC_FOUND_ROWS (.*) FROM/i',$sql,$selectmatches);
        $selects = array();
        if(isset($selectmatches[1])&&!empty($selectmatches[1])&&stripos($selectmatches[1],' AS ')!==false)
        {
            $theselects = explode(', ',$selectmatches[1]);
            if(empty($theselects))
                $theselects = explode(',',$selectmatches[1]);
            foreach($theselects as $selected)
            {
                $selectfield = explode(' AS ',$selected);
                if(count($selectfield)==2)
                {
                    $field = trim(trim($selectfield[1]),'`');
                    $real_field = trim(trim($selectfield[0]),'`');
                    $selects[$field] = $real_field;
                }
            }
        }
        if(false!==$this->search&&!empty($this->search_columns))
        {
            if(false!==$this->search_query&&0<strlen($this->search_query))
            {
                foreach($this->search_columns as $key=>$column)
                {
                    $attributes = $column;
                    if(!is_array($attributes))
                        $attributes = $this->setup_column($column);
                    if(false===$attributes['search'])
                        continue;
                    if(in_array($attributes['type'],array('date','time','datetime')))
                        continue;
                    if(is_array($column))
                        $column = $key;
                    if(isset($this->filters[$column]))
                        continue;
                    $columnfield = '`'.$column.'`';
                    if(isset($selects[$column]))
                        $columnfield = '`'.$selects[$column].'`';
                    if($attributes['real_name']!==false)
                        $columnfield = $attributes['real_name'];
                    if($attributes['group_related']!==false)
                        $having_sql[] = "$columnfield LIKE '%".$this->sanitize($this->search_query)."%'";
                    else
                        $other_sql[] = "$columnfield LIKE '%".$this->sanitize($this->search_query)."%'";
                }
                if(!empty($other_sql))
                {
                    $other_sql = array('('.implode(' OR ',$other_sql).')');
                }
                if(!empty($having_sql))
                {
                    $having_sql = array('('.implode(' OR ',$having_sql).')');
                }
            }
            foreach($this->filters as $filter)
            {
                if(!isset($this->search_columns[$filter]))
                    continue;
                $filterfield = '`'.$filter.'`';
                if(isset($selects[$filter]))
                    $filterfield = '`'.$selects[$filter].'`';
                if($this->search_columns[$filter]['real_name']!==false)
                    $filterfield = $this->search_columns[$filter]['real_name'];
                if(in_array($this->search_columns[$filter]['type'],array('date','datetime')))
                {
                    $start = date_i18n('Y-m-d').($this->search_columns[$filter]['type']=='datetime'?' 00:00:00':'');
                    $end = date_i18n('Y-m-d').($this->search_columns[$filter]['type']=='datetime'?' 23:59:59':'');
                    if(strlen($this->get_var('filter_'.$filter.'_start',$this->search_columns[$filter]['filter_default']))<1&&strlen($this->get_var('filter_'.$filter.'_end',$this->search_columns[$filter]['filter_ongoing_default']))<1)
                        continue;
                    if(0<strlen($this->get_var('filter_'.$filter.'_start',$this->search_columns[$filter]['filter_default'])))
                        $start = date_i18n('Y-m-d',strtotime($this->get_var('filter_'.$filter.'_start',$this->search_columns[$filter]['filter_default']))).($this->search_columns[$filter]['type']=='datetime'?' 00:00:00':'');
                    if(0<strlen($this->get_var('filter_'.$filter.'_end',$this->search_columns[$filter]['filter_ongoing_default'])))
                        $end = date_i18n('Y-m-d',strtotime($this->get_var('filter_'.$filter.'_end',$this->search_columns[$filter]['filter_ongoing_default']))).($this->search_columns[$filter]['type']=='datetime'?' 23:59:59':'');
                    if(false!==$this->search_columns[$filter]['date_ongoing'])
                    {
                        $date_ongoing = $this->search_columns[$filter]['date_ongoing'];
                        if(isset($selects[$date_ongoing]))
                            $date_ongoing = $selects[$date_ongoing];
                        if($this->search_columns[$filter]['group_related']!==false)
                            $having_sql[] = "(($filterfield <= '$start' OR ($filterfield >= '$start' AND $filterfield <= '$end')) AND ($date_ongoing >= '$start' OR ($date_ongoing >= '$start' AND $date_ongoing <= '$end')))";
                        else
                            $other_sql[] = "(($filterfield <= '$start' OR ($filterfield >= '$start' AND $filterfield <= '$end')) AND ($date_ongoing >= '$start' OR ($date_ongoing >= '$start' AND $date_ongoing <= '$end')))";
                    }
                    else
                    {
                        if($this->search_columns[$filter]['group_related']!==false)
                            $having_sql[] = "($filterfield BETWEEN '$start' AND '$end')";
                        else
                            $other_sql[] = "($filterfield BETWEEN '$start' AND '$end')";
                    }
                }
                elseif(0<strlen($this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']))&&'related'==$this->search_columns[$filter]['type']&&false!==$this->search_columns[$filter]['related'])
                {
                    if(!is_array($this->search_columns[$filter]['related']))
                    {
                        $search_value = $this->sanitize($this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']));
                        if(intval($search_value)<0)
                            $search_value = "'".$search_value."'";
                        else
                            $search_value = intval($search_value);
                        $related_filterfield = '`'.$filter.'`.`'.$this->search_columns[$filter]['related_id'].'`';
                        if(isset($selects[$filter]))
                            $related_filterfield = '`'.$selects[$filter].'`.`'.$this->search_columns[$filter]['related_id'].'`';
                        if($this->search_columns[$filter]['real_name']!==false)
                            $related_filterfield = $this->search_columns[$filter]['real_name'];
                        if($this->search_columns[$filter]['group_related']!==false)
                            $having_sql[] = "{$related_filterfield} = {$search_value}";
                        else
                            $other_sql[] = "{$related_filterfield} = {$search_value}";
                    }
                    else
                    {
                        if($this->search_columns[$filter]['group_related']!==false)
                            $having_sql[] = "$filterfield LIKE '%".$this->sanitize($this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']))."%'";
                        else
                            $other_sql[] = "$filterfield LIKE '%".$this->sanitize($this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']))."%'";
                    }
                }
                elseif(0<strlen($this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default'])))
                {
                    if($this->search_columns[$filter]['group_related']!==false)
                        $having_sql[] = "$filterfield LIKE '%".$this->sanitize($this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']))."%'";
                    else
                        $other_sql[] = "$filterfield LIKE '%".$this->sanitize($this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']))."%'";
                }
            }
            if(!empty($other_sql))
            {
                if(false===stripos($sql,' WHERE '))
                    $wheresql .= ' WHERE ('.implode(' AND ',$other_sql).') ';
                elseif(false!==stripos($sql,' WHERE %%WHERE%% ') || false===stripos($sql,' %%WHERE%% '))
                    $wheresql .= ' ('.implode(' AND ',$other_sql).') AND ';
                else
                    $wheresql .= ' AND ('.implode(' AND ',$other_sql).') ';
            }
            if(!empty($having_sql))
            {
                if(false===stripos($sql,' HAVING '))
                    $havingsql .= ' HAVING ('.implode(' AND ',$having_sql).') ';
                elseif(false!==stripos($sql,' HAVING %%HAVING%% ') || false===stripos($sql,' %%HAVING%% '))
                    $havingsql .= ' ('.implode(' AND ',$havingsql).') AND ';
                else
                    $havingsql .= ' AND ('.implode(' AND ',$havingsql).') ';
            }
        }
        if(false!==$this->order&&(false===$this->reorder||$this->action!='reorder'))
            $ordersql = trim($this->order.' '.$this->order_dir);
        elseif(false!==$this->reorder&&$this->action=='reorder')
            $ordersql = trim($this->reorder_order.' '.$this->reorder_order_dir);
        if(!empty($ordersql))
        {
            if(false===stripos($sql,' ORDER BY '))
                $ordersql = ' ORDER BY '.$ordersql;
            elseif(false!==stripos($sql,' ORDER BY %%ORDERBY%% '))
                $ordersql = $ordersql.', ';
            elseif(false!==stripos($sql,' %%ORDERBY%% '))
                $ordersql = ','.$ordersql;
            else
                $ordersql = $ordersql.', ';
        }
        if(false!==$this->pagination&&!$full)
        {
            $start = ($this->page-1)*$this->limit;

            $limitsql .= (int) $start.','. (int) $this->limit;
        }
        else
            $sql = str_replace (' LIMIT %%LIMIT%% ','',$sql);
        if(stripos($sql,'%%WHERE%%')===false&&stripos($sql,' WHERE ')===false)
        {
            if(stripos($sql,' GROUP BY ')!==false)
                $sql = str_replace(' GROUP BY ',' %%WHERE%% GROUP BY ',$sql);
            elseif(stripos($sql,' ORDER BY ')!==false)
                $sql = str_replace(' ORDER BY ',' %%WHERE%% ORDER BY ',$sql);
            elseif(stripos($sql,' LIMIT ')!==false)
                $sql = str_replace(' LIMIT ',' %%WHERE%% LIMIT ',$sql);
            else
                $sql .= ' %%WHERE%% ';
        }
        elseif(stripos($sql,'%%WHERE%%')===false)
            $sql = str_replace(' WHERE ',' WHERE %%WHERE%% ',$sql);
        if(stripos($sql,'%%HAVING%%')===false&&stripos($sql,' HAVING ')===false)
        {
            if(stripos($sql,' ORDER BY ')!==false)
                $sql = str_replace(' ORDER BY ',' %%HAVING%% ORDER BY ',$sql);
            elseif(stripos($sql,' LIMIT ')!==false)
                $sql = str_replace(' LIMIT ',' %%HAVING%% LIMIT ',$sql);
            else
                $sql .= ' %%HAVING%% ';
        }
        elseif(stripos($sql,'%%HAVING%%')===false)
            $sql = str_replace(' HAVING ',' HAVING %%HAVING%% ',$sql);
        if(stripos($sql,'%%ORDERBY%%')===false&&stripos($sql,' ORDER BY ')===false)
        {
            if(stripos($sql,' LIMIT ')!==false)
                $sql = str_replace(' LIMIT ',' %%ORDERBY%% LIMIT ',$sql);
            else
                $sql .= ' %%ORDERBY%% ';
        }
        elseif(stripos($sql,'%%ORDERBY%%')===false)
            $sql = str_replace(' ORDER BY ',' ORDER BY %%ORDERBY%% ',$sql);
        if(stripos($sql,'%%LIMIT%%')===false&&stripos($sql,' LIMIT ')===false&&!empty($limitsql))
            $sql .= ' LIMIT %%LIMIT%% ';
        elseif(stripos($sql,'%%LIMIT%%')===false)
            $sql = str_replace(' LIMIT ',' LIMIT %%LIMIT%% ',$sql);
        $sql = str_replace('%%WHERE%%',$wheresql,$sql);
        $sql = str_replace('%%HAVING%%',$havingsql,$sql);
        $sql = str_replace('%%ORDERBY%%',$ordersql,$sql);
        $sql = str_replace('%%LIMIT%%',$limitsql,$sql);
        $sql = str_replace('``','`',$sql);
        $sql = str_replace('  ',' ',$sql);
        if (false !== $this->sql_count) {
            $wheresql = $havingsql = $ordersql = $limitsql = '';
            $sql_count = ' '.str_replace(array("\n","\r",'  '),' ',' '.$this->sql_count).' ';
            $calc_found_sql = 'SQL_CALC_FOUND_ROWS';
            if ($full || false !== $this->sql_count)
                $calc_found_sql = '';
            $sql_count = str_ireplace(' SELECT '," SELECT {$calc_found_sql} ",str_ireplace(' SELECT SQL_CALC_FOUND_ROWS ',' SELECT ',$sql_count));
            if(false!==$this->search&&!empty($this->search_columns))
            {
                if(!empty($other_sql))
                {
                    if(false===stripos($sql,' WHERE '))
                        $wheresql .= ' WHERE ('.implode(' AND ',$other_sql).') ';
                    elseif(false!==stripos($sql,' WHERE %%WHERE%% ') || false===stripos($sql,' %%WHERE%% '))
                        $wheresql .= ' ('.implode(' AND ',$other_sql).') AND ';
                    else
                        $wheresql .= ' AND ('.implode(' AND ',$other_sql).') ';
                }
                if(!empty($having_sql))
                {
                    if(false===stripos($sql,' HAVING '))
                        $havingsql .= ' HAVING ('.implode(' AND ',$having_sql).') ';
                    elseif(false!==stripos($sql,' HAVING %%HAVING%% ') || false===stripos($sql,' %%HAVING%% '))
                        $havingsql .= ' ('.implode(' AND ',$havingsql).') AND ';
                    else
                        $havingsql .= ' AND ('.implode(' AND ',$havingsql).') ';
                }
            }
            $limitsql .= '1';
            if(stripos($sql_count,'%%WHERE%%')===false&&stripos($sql_count,' WHERE ')===false)
            {
                if(stripos($sql_count,' GROUP BY ')!==false)
                    $sql_count = str_replace(' GROUP BY ',' %%WHERE%% GROUP BY ',$sql_count);
                elseif(stripos($sql_count,' ORDER BY ')!==false)
                    $sql_count = str_replace(' ORDER BY ',' %%WHERE%% ORDER BY ',$sql_count);
                elseif(stripos($sql_count,' LIMIT ')!==false)
                    $sql_count = str_replace(' LIMIT ',' %%WHERE%% LIMIT ',$sql_count);
                else
                    $sql_count .= ' %%WHERE%% ';
            }
            elseif(stripos($sql_count,'%%WHERE%%')===false)
                $sql_count = str_replace(' WHERE ',' WHERE %%WHERE%% ',$sql_count);
            if(stripos($sql_count,'%%HAVING%%')===false&&stripos($sql_count,' HAVING ')===false)
            {
                if(stripos($sql_count,' ORDER BY ')!==false)
                    $sql_count = str_replace(' ORDER BY ',' %%HAVING%% ORDER BY ',$sql_count);
                elseif(stripos($sql_count,' LIMIT ')!==false)
                    $sql_count = str_replace(' LIMIT ',' %%HAVING%% LIMIT ',$sql_count);
                else
                    $sql_count .= ' %%HAVING%% ';
            }
            elseif(stripos($sql_count,'%%HAVING%%')===false)
                $sql_count = str_replace(' HAVING ',' HAVING %%HAVING%% ',$sql_count);
            if(stripos($sql_count,'%%LIMIT%%')===false&&stripos($sql_count,' LIMIT ')===false&&!empty($limitsql))
                $sql_count .= ' LIMIT %%LIMIT%% ';
            elseif(stripos($sql_count,'%%LIMIT%%')===false)
                $sql_count = str_replace(' LIMIT ',' LIMIT %%LIMIT%% ',$sql_count);
            $sql_count = str_replace('%%WHERE%%',$wheresql,$sql_count);
            $sql_count = str_replace('%%HAVING%%',$havingsql,$sql_count);
            $sql_count = str_replace('%%LIMIT%%',$limitsql,$sql_count);
            $sql_count = str_replace('``','`',$sql_count);
            $sql_count = str_replace('  ',' ',$sql_count);
        }
        if (current_user_can('manage_options') && isset($_GET['debug']) && 1 == $_GET['debug'])
            echo "<textarea cols='130' rows='30'>" . esc_textarea( $sql ) . "</textarea>";
        if(false!==$this->default_none&&false===$this->search_query&&false===$full&&empty($wheresql)&&empty($havingsql))
            return false;
        $results = $wpdb->get_results($sql,ARRAY_A);
        if (current_user_can('manage_options') && isset($_GET['debug']) && 1 == $_GET['debug'])
            $wpdb->show_errors = true;
        if($full)
            $this->full_data = $results;
        else
            $this->data = $results;
        $totals = false;
        if(!empty($results) && is_array($results)) foreach($this->columns as $key=>$column)
        {
            if (false===$totals)
                $totals = @current($results);
            if (empty($totals) || !is_array($totals))
                continue;
            $attributes = $column;
            if(!is_array($attributes))
                $attributes = $this->setup_column($column);
            if(true!==$attributes['total_field'])
                continue;
            if(is_array($column))
                $column = $key;
            if(false===$this->totals)
                $this->totals = array();
            $this->totals[$column] = $totals['wp_admin_ui_total_'.$column];
        }
        if(empty($this->columns))
            $this->catch_columns($full);
        if($full) {
	        return $results;
        }
        if (false !== $this->sql_count && ! empty( $sql_count ))
            $wpdb->query($sql_count);
        $total = @current($wpdb->get_col("SELECT FOUND_ROWS()"));
        $total = $this->do_hook('get_data_total',$total,$full);
        if(is_numeric($total))
            $this->total = $total;

        return $results;
    }
    function manage ($reorder=0)
    {
        global $wpdb;
        $this->do_hook('manage',$reorder);
        if(isset($this->custom['manage'])&&function_exists("{$this->custom['manage']}"))
            return call_user_func( $this->custom['manage'],$this,$reorder);
?>
<div class="wrap">
    <div id="icon-edit-pages" class="icon32"<?php if(false!==$this->icon){ ?> style="background-position:0 0;background-image:url(<?php echo esc_url( $this->icon ); ?>);"<?php } ?>><br /></div>
    <h2><?php echo ($reorder==0||false===$this->reorder?$this->heading['manage']:$this->heading['reorder']); ?> <?php echo esc_html( $this->items ); if($reorder==1&&false!==$this->reorder){ ?> <small>(<a href="<?php echo esc_url( $this->var_update(array('action'=>'manage','id'=>'')) ); ?>">&laquo; Back to Manage</a>)</small><?php } ?></h2>
<?php
        if(isset($this->custom['header'])&&function_exists("{$this->custom['header']}"))
            echo call_user_func( $this->custom['header'],$this);
        if(empty($this->data))
            $this->get_data();
        if (empty($this->columns))
            $this->catch_columns();
        if(false!==$this->export&&$this->action=='export')
            $this->export();
        if((!empty($this->data)||false!==$this->search_query||false!==$this->default_none)&&false!==$this->search)
        {
?>
    <form id="posts-filter" action="" method="get">
        <p class="search-box">
<?php
            $excluded_filters = array();
            foreach($this->filters as $filter)
            {
                $excluded_filters[] = 'filter_'.$filter.'_start';
                $excluded_filters[] = 'filter_'.$filter.'_end';
                $excluded_filters[] = 'filter_'.$filter;
            }
            $excluded_filters = array_merge($excluded_filters,array('search_query'));
            $this->hidden_vars($excluded_filters);
            foreach($this->filters as $filter)
            {
                if(!isset($this->search_columns[$filter]))
                    continue;
                $date_exists = false;
                if(in_array($this->search_columns[$filter]['type'],array('date','datetime')))
                {
                    if(false===$date_exists)
                    {
?>
<link type="text/css" rel="stylesheet" href="<?php echo $this->assets_url; ?>/jquery/ui.datepicker.css" />
<script type="text/javascript">
jQuery(document).ready(function(){
    jQuery.getScript('<?php echo $this->assets_url; ?>/jquery/ui.datepicker.js',function(){jQuery('input.admin_ui_date').datepicker();});
});
</script>
<?php
                    }
                    $date_exists = true;
                    $start = $this->get_var('filter_'.$filter.'_start',$this->search_columns[$filter]['filter_default']);
                    $end = $this->get_var('filter_'.$filter.'_end',$this->search_columns[$filter]['filter_ongoing_default']);
?>&nbsp;&nbsp;
            <label for="admin_ui_filter_<?php echo $filter; ?>_start"><?php echo $this->search_columns[$filter]['filter_label']; ?>:</label>
            <input type="text" name="filter_<?php echo $filter; ?>_start" class="admin_ui_filter admin_ui_date" id="admin_ui_filter_<?php echo $filter; ?>_start" value="<?php echo (false!==$start&&0<strlen($start)?date_i18n('m/d/Y',strtotime($start)):''); ?>" /> <label for="admin_ui_filter_<?php echo $filter; ?>_end">to</label>
            <input type="text" name="filter_<?php echo $filter; ?>_end" class="admin_ui_filter admin_ui_date" id="admin_ui_filter_<?php echo $filter; ?>_end" value="<?php echo (false!==$end&&0<strlen($end)?date_i18n('m/d/Y',strtotime($end)):''); ?>" />
<?php
                }
                elseif('related'==$this->search_columns[$filter]['type']&&false!==$this->search_columns[$filter]['related'])
                {
                    if(!is_array($this->search_columns[$filter]['related']))
                    {
                        $related = $wpdb->get_results('SELECT `'.$this->sanitize( $this->search_columns[$filter]['related_id'] ).'`,`'.$this->sanitize( $this->search_columns[$filter]['related_field'] ).'` FROM '.$this->search_columns[$filter]['related'].(!empty($this->search_columns[$filter]['related_sql'])?' '.$this->search_columns[$filter]['related_sql']:''));
?>
            <label for="admin_ui_filter_<?php echo $filter; ?>"><?php echo $this->search_columns[$filter]['filter_label']; ?>:</label>
            <select name="filter_<?php echo $filter; ?><?php echo (false!==$this->search_columns[$filter]['related_multiple']?'[]':''); ?>" id="admin_ui_filter_<?php echo $filter; ?>"<?php echo (false!==$this->search_columns[$filter]['related_multiple']?' size="10" style="height:auto;" MULTIPLE':''); ?>>
                <option value="">-- Show All --</option>
<?php
                        $selected = $this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']);
                        foreach($related as $option)
                        {
?>
                <option value="<?php echo $option->{$this->search_columns[$filter]['related_id']}; ?>"<?php echo ($option->{$this->search_columns[$filter]['related_id']}==$selected?' SELECTED':''); ?>><?php echo $option->{$this->search_columns[$filter]['related_field']}; ?></option>
<?php
                        }
?>
            </select>
<?php
                    }
                    else
                    {
                        $related = $this->search_columns[$filter]['related'];
?>
            <label for="admin_ui_filter_<?php echo $filter; ?>"><?php echo $this->search_columns[$filter]['filter_label']; ?>:</label>
            <select name="filter_<?php echo $filter; ?><?php echo (false!==$this->search_columns[$filter]['related_multiple']?'[]':''); ?>" id="admin_ui_filter_<?php echo $filter; ?>"<?php echo (false!==$this->search_columns[$filter]['related_multiple']?' size="10" style="height:auto;" MULTIPLE':''); ?>>
                <option value="">-- Show All --</option>
<?php
                        $selected = $this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']);
                        foreach($related as $option_id=>$option)
                        {
?>
                <option value="<?php echo $option_id; ?>"<?php echo ($option->id==$this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default'])?' SELECTED':''); ?>><?php echo $option; ?></option>
<?php
                        }
?>
            </select>
<?php
                    }
                }
                elseif('bool'==$this->search_columns[$filter]['type'])
                {
?>
            <label for="admin_ui_filter_<?php echo $filter; ?>"><?php echo $this->search_columns[$filter]['filter_label']; ?>:</label>
            <select name="filter_<?php echo $filter; ?>" id="admin_ui_filter_<?php echo $filter; ?>">
                <option value="">-- Show All --</option>
                <option value="1"<?php echo ((1 == $this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default'])) ? ' SELECTED' : ''); ?>>Yes</option>
                <option value="0"<?php echo (('' != $this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']) && 0 == $this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default'])) ? ' SELECTED' : ''); ?>>No</option>
            </select>
<?php
                }
                else
                {
?>
            <label for="admin_ui_filter_<?php echo $filter; ?>"><?php echo $this->search_columns[$filter]['filter_label']; ?>:</label>
            <input type="text" name="filter_<?php echo $filter; ?>" class="admin_ui_filter" id="admin_ui_filter_<?php echo $filter; ?>" value="<?php echo $this->get_var('filter_'.$filter,$this->search_columns[$filter]['filter_default']); ?>" />
<?php
                }
            }
?>&nbsp;&nbsp;
            <label<?php echo(empty($this->filters)?' class="screen-reader-text"':''); ?> for="page-search-input">Search:</label>
            <input type="text" name="search_query" id="page-search-input" value="<?php echo $this->search_query; ?>" />
            <input type="submit" value="Search" class="button" />
<?php
            if(false!==$this->search_query)
            {
                $clear_filters = array();
                foreach($this->filters as $filter)
                {
                    $clear_filters['filter_'.$filter.'_start'] = '';
                    $clear_filters['filter_'.$filter.'_end'] = '';
                    $clear_filters['filter_'.$filter] = '';
                }
?>
            &nbsp;&nbsp;&nbsp;<small>[<a href="<?php echo $this->var_update($clear_filters,array('order','order_dir','limit')); ?>">Reset Filters</a>]</small>
<?php
        }
?>
        </p>
    </form>
<?php
        }
        else
        {
?>
    <br class="clear" />
    <br class="clear" />
<?php
        }
?>
    <div class="tablenav">
<?php
        if(!empty($this->data)&&false!==$this->pagination)
        {
?>
        <div class="tablenav-pages">
            Show per page:<?php $this->limit(); ?> &nbsp;|&nbsp;
<?php $this->pagination( true ); ?>
        </div>
<?php
        }
        if($reorder==1)
        {
?>
            <input type="button" value="Update Order" class="button" onclick="jQuery('form.admin_ui_reorder_form').submit();" />
            <input type="button" value="Cancel" class="button" onclick="document.location='<?php echo $this->var_update(array('action'=>'manage')); ?>';" />
<?php
        }
        elseif($this->add||$this->export)
        {
?>
        <div class="alignleft actions">
<?php
            if($this->add)
            {
?>
            <input type="button" value="Add New <?php echo $this->item; ?>" class="button" onclick="document.location='<?php echo $this->var_update(array('action'=>'add')); ?>';" />
<?php
            }
            if($this->reorder)
            {
?>
            <input type="button" value="Reorder" class="button" onclick="document.location='<?php echo $this->var_update(array('action'=>'reorder')); ?>';" />
<?php
            }
            if($this->export)
            {
?>
            <strong>Export:</strong>
            <input type="button" value=" CSV " class="button" onclick="document.location='<?php echo $this->var_update(array('action'=>'export','export_type'=>'csv')); ?>';" />
            <input type="button" value=" TSV " class="button" onclick="document.location='<?php echo $this->var_update(array('action'=>'export','export_type'=>'tsv')); ?>';" />
            <input type="button" value=" XML " class="button" onclick="document.location='<?php echo $this->var_update(array('action'=>'export','export_type'=>'xml')); ?>';" />
            <input type="button" value=" JSON " class="button" onclick="document.location='<?php echo $this->var_update(array('action'=>'export','export_type'=>'json')); ?>';" />
<?php
            }
?>
        </div>
<?php
        }
?>
        <br class="clear" />
    </div>
    <div class="clear"></div>
<?php
        if(empty($this->data)&&false!==$this->default_none&&false===$this->search_query)
        {
?>
    <p>Please use the search filter(s) above to display data<?php if($this->export){ ?>, or click on an Export to download a full copy of the data<?php } ?>.</p>
<?php
        }
        else
            $this->table($reorder);
        if(!empty($this->data))
        {
?>
    <div class="tablenav">
        <div class="tablenav-pages">
<?php $this->pagination(); ?>
        <br class="clear" />
        </div>
    </div>
<?php
        }
        if(!empty($this->data) && $reorder != 1 && false !== $this->totals)
        {
?>
<h3 align="center">Totals</h3>
<table class="widefat fixed admin_ui_table" cellspacing="0"<?php echo ($reorder==1&&$this->reorder?' id="admin_ui_reorder"':''); ?> style="width:auto;margin:0 auto;">
    <thead>
        <tr>
<?php
            $columns = array();
            foreach($this->columns as $column=>$attributes)
            {
                if(!isset($this->totals[$column]))
                    continue;
                $label = ucwords(str_replace('_',' ',$column));
                if(false!==$this->get_var('label',false,false,$attributes))
                    $label = $attributes['label'];
?>
            <th scope="col" class="manage-column"><?php echo $label; ?></th>
<?php
                if($attributes['type']=='number')
                    $total = intval($this->totals[$column]);
                elseif($attributes['type']=='decimal')
                    $total = number_format($this->totals[$column],2);
                else
                    $total = $this->totals[$column];
                $columns[$column] = $total;
            }
?>
        </tr>
    </thead>
    <tbody>
        <tr>
<?php
            foreach($columns as $column=>$total)
            {
?>
            <td><?php echo $total; ?></td>
<?php
            }
?>
        </tr>
    </tbody>
</table>
<?php
        }
?>
</div>
<?php
    }
    function table ($reorder=0)
    {
        if($reorder==1&&false!==$this->reorder)
        {
            if (!wp_script_is('jquery-ui-core', 'queue') && !wp_script_is('jquery-ui-core', 'to_do') && !wp_script_is('jquery-ui-core', 'done'))
                wp_print_scripts('jquery-ui-core');
            if (!wp_script_is('jquery-ui-sortable', 'queue') && !wp_script_is('jquery-ui-sortable', 'to_do') && !wp_script_is('jquery-ui-sortable', 'done'))
                wp_print_scripts('jquery-ui-sortable');
        }
        $this->do_hook('table',$reorder);
        if(isset($this->custom['table'])&&function_exists("{$this->custom['table']}"))
            return call_user_func( $this->custom['table'],$this,$reorder);
        if(empty($this->data))
        {
?>
<p>No items found</p>
<?php
            return false;
        }
        if($reorder==1&&$this->reorder)
        {
?>
<style type="text/css">
table.widefat.fixed tbody.sortable tr { height:50px; }
.dragme {
    background: url(<?php echo $this->assets_url; ?>/move.png) no-repeat;
    background-position:8px 8px;
    cursor:pointer;
}
.dragme strong { margin-left:30px; }
</style>
<form action="<?php echo $this->var_update(array('action'=>'reorder','do'=>'save')); ?>" method="post" class="admin_ui_reorder_form">
<?php
        }
        $column_index = 'columns';
        if($reorder==1)
            $column_index = 'reorder_columns';
        if(false===$this->$column_index||empty($this->$column_index))
            return $this->error('<strong>Error:</strong> Invalid Configuration - Missing "columns" definition.');
?>
<table class="widefat page fixed admin_ui_table wp-list-table" cellspacing="0"<?php echo ($reorder==1&&$this->reorder?' id="admin_ui_reorder"':''); ?>>
    <thead>
        <tr>
<?php
        $name_column = false;
        $columns = array();
        if(!empty($this->$column_index)) foreach($this->columns as $column=>$attributes)
        {
            if(!is_array($attributes)) {
                $column = $attributes;
                $attributes = $this->setup_column($column);
            }
            if(false===$attributes['display'])
                continue;
            if(false===$name_column)
                $id = 'title';
            else
                $id = '';
            if(false!==$this->get_var('type',false,false,$attributes))
            {
                if($attributes['type']=='other')
                    $id = '';
                if($attributes['type']=='date'||$attributes['type']=='datetime'||$attributes['type']=='time')
                    $id = 'date';
            }
            if(false===$name_column&&$id=='title')
                $name_column = true;
            $label = ucwords(str_replace('_',' ',$column));
            if(false!==$this->get_var('label',false,false,$attributes))
                $label = $attributes['label'];
            $columns[$column] = array('label'=>$label,'id'=>$id);
            $columns[$column] = array_merge($columns[$column],$attributes);
            $dir = 'ASC';
            if($this->order==$column&&$this->order_dir=='ASC')
                $dir = 'DESC';
?>
            <th scope="col" id="<?php echo $id; ?>" class="manage-column column-<?php echo $id; ?>"><a href="<?php echo $this->var_update(array('order'=>$column,'order_dir'=>$dir),array('limit','search_query')); ?>"><?php echo $label; ?></a></th>
<?php
        }
?>
        </tr>
    </thead>
    <tfoot>
        <tr>
<?php
    if(!empty($columns)) foreach($columns as $column=>$attributes)
        {
?>
            <th scope="col" class="manage-column column-<?php echo $attributes['id']; ?>"><?php echo $attributes['label']; ?></th>
<?php
        }
?>
        </tr>
    </tfoot>
    <tbody<?php echo ($reorder==1&&$this->reorder?' class="sortable"':''); ?>>
<?php
        if(!empty($this->data)) foreach($this->data as $row)
        {
?>
        <tr id="item-<?php echo $row[$this->identifier]; ?>" class="iedit">
<?php
            foreach($columns as $column=>$attributes)
            {
                if(!is_array($attributes)) {
                    $column = $attributes;
                    $attributes = $this->setup_column($column);
                }
                if(false===$attributes['display'])
                    continue;
                $row[$column] = $this->field_value($row[$column], $column, $attributes);
                if(false!==$attributes['custom_display']&&function_exists("{$attributes['custom_display']}"))
                    $row[$column] = $attributes['custom_display']($row[$column],$row,$this);
                else {
                    $row[$column] = wp_kses_post( $row[ $column ] );
                }
                if($attributes['id']=='title')
                {
                    if($this->view&&($reorder==0||false===$this->reorder))
                    {
?>
            <td class="post-title page-title column-title"><strong><a class="row-title" href="<?php echo $this->var_update(array('action'=>'view','id'=>$row[$this->identifier])); ?>" title="View &#8220;<?php echo htmlentities($row[$column]); ?>&#8221;"><?php echo $row[$column]; ?></a></strong>
<?php
                    }
                    elseif($this->edit&&($reorder==0||false===$this->reorder))
                    {
?>
            <td class="post-title page-title column-title"><strong><a class="row-title" href="<?php echo $this->var_update(array('action'=>'edit','id'=>$row[$this->identifier])); ?>" title="Edit &#8220;<?php echo htmlentities($row[$column]); ?>&#8221;"><?php echo $row[$column]; ?></a></strong>
<?php
                    }
                    else
                    {
?>
            <td class="post-title page-title column-title<?php echo ($reorder==1&&$this->reorder?' dragme':''); ?>"><strong><?php echo $row[$column]; ?></strong>
<?php
                    }
                    if($reorder==0||false===$this->reorder)
                    {
                        $actions = array();
                        if($this->view)
                            $actions['view'] = '<span class="view"><a href="'.$this->var_update(array('action'=>'view','id'=>$row[$this->identifier])).'" title="View this item">View</a></span>';
                        if($this->edit)
                            $actions['edit'] = '<span class="edit"><a href="'.$this->var_update(array('action'=>'edit','id'=>$row[$this->identifier])).'" title="Edit this item">Edit</a></span>';
                        if($this->duplicate)
                            $actions['duplicate'] = '<span class="edit"><a href="'.$this->var_update(array('action'=>'duplicate','id'=>$row[$this->identifier])).'" title="Duplicate this item">Duplicate</a></span>';
                        if($this->delete)
                            $actions['delete'] = '<span class="delete"><a class="submitdelete" title="Delete this item" href="'.$this->var_update(array('action'=>'delete','id'=>$row[$this->identifier],'_wpnonce'=>wp_create_nonce('wp-admin-ui-delete'))).'" onclick="if(confirm(\'You are about to delete this item \''.htmlentities($row[$column]).'\'\n \'Cancel\' to stop, \'OK\' to delete.\')){return true;}return false;">Delete</a></span>';
                        if(is_array($this->custom))
                        {
                            foreach($this->custom as $custom_action=>$custom_data)
                            {
                                if(is_array($custom_data)&&isset($custom_data['link']))
                                {
                                    if(!in_array($custom_action,array('add','view','edit','duplicate','delete','save','readonly','export','reorder')))
                                    {
                                        if(!isset($custom_data['label']))
                                            $custom_data['label'] = ucwords(str_replace('_',' ',$custom_action));
                                        $actions[$custom_action] = '<span class="edit"><a href="'.$this->parse_template_string($custom_data['link'],$row).'" title="'.esc_attr($custom_data['label']).' this item">'.$custom_data['label'].'</a></span>';
                                    }
                                }
                            }
                        }
                        $actions = $this->do_hook('row_actions',$actions);
?>
                <div class="row-actions">
<?php
                        if(isset($this->custom['actions_start'])&&function_exists("{$this->custom['actions_start']}"))
                            call_user_func( $this->custom['actions_start'],$this,$row);
                        echo implode(' | ',$actions);
                        if(isset($this->custom['actions_end'])&&function_exists("{$this->custom['actions_end']}"))
                            call_user_func( $this->custom['actions_end'],$this,$row);
?>
                </div>
<?php
                    }
                    else
                    {
?>
                <input type="hidden" name="order[]" value="<?php echo $row[$this->identifier]; ?>" />
<?php
                    }
?>
            </td>
<?php
                }
                elseif('date'==$attributes['type'])
                {
?>
            <td class="date column-date"><abbr title="<?php echo $row[$column]; ?>"><?php echo $row[$column]; ?></abbr></td>
<?php
                }
                elseif('time'==$attributes['type'])
                {
?>
            <td class="date column-date"><abbr title="<?php echo $row[$column]; ?>"><?php echo $row[$column]; ?></abbr></td>
<?php
                }
                elseif('datetime'==$attributes['type'])
                {
?>
            <td class="date column-date"><abbr title="<?php echo $row[$column]; ?>"><?php echo $row[$column]; ?></abbr></td>
<?php
                }
                elseif('related'==$attributes['type']&&false!==$attributes['related'])
                {
?>
            <td class="author column-author"><?php echo $row[$column]; ?></td>
<?php
                }
                elseif($attributes['type']=='bool')
                {
?>
            <td class="author column-author"><?php echo $row[$column]; ?></td>
<?php
                }
                elseif($attributes['type']=='number')
                {
?>
            <td class="author column-author"><?php echo $row[$column]; ?></td>
<?php
                }
                elseif($attributes['type']=='decimal')
                {
?>
            <td class="author column-author"><?php echo $row[$column]; ?></td>
<?php
                }
                else
                {
?>
            <td class="author column-author"><?php echo $row[$column]; ?></td>
<?php
                }
            }
?>
        </tr>
<?php
        }
?>
    </tbody>
</table>
<?php
        if($reorder==1&&false!==$this->reorder)
        {
?>
</form>
<?php
        }
?>
<script type="text/javascript">
jQuery('table.widefat tbody tr:even').addClass('alternate');
<?php
        if($reorder==1&&false!==$this->reorder)
        {
?>
jQuery(document).ready(function(){
    jQuery(".sortable").sortable({axis: "y", handle: ".dragme"});
    jQuery(".sortable").bind('sortupdate', function(event, ui){
        jQuery('table.widefat tbody tr').removeClass('alternate');
        jQuery('table.widefat tbody tr:even').addClass('alternate');
    });
});
<?php
        }
?>
</script>
<?php
    }

    /**
     * @param bool $header
     *
     * @return mixed
     */
    public function pagination ( $header = false ) {
        $this->do_hook('pagination');
        if(isset($this->custom['pagination'])&&function_exists("{$this->custom['pagination']}"))
            return call_user_func( $this->custom['pagination'],$this);

        $page = $this->page;
        $rows_per_page = $this->limit;
        $total_rows = $this->total;
        $total_pages = ceil($total_rows / $rows_per_page);

        $request_uri = $this->var_update(array('pg'=>''),array('limit','order','order_dir','search_query'));

        $append = false;

        if ( false !== strpos( $request_uri, '?' ) )
            $append = true;

        if ( $header || 1 != $total_rows ) {
            $singular_label = strtolower( $this->item );
            $plural_label = strtolower( $this->items );
?>
        <span class="displaying-num"><?php echo number_format_i18n( $total_rows ) . ' ' . _n( $singular_label, $plural_label, $total_rows ) ?></span>
<?php
        }

		if ( 1 < $total_pages ) {
?>
		<a class="first-page<?php echo ( 1 < $this->page ) ? '' : ' disabled'; ?>" title="<?php _e( 'Go to the first page' ); ?>" href="<?php echo $request_uri . ( $append ? '&' : '?' ) . 'pg' . '=1'; ?>">&laquo;</a>
		<a class="prev-page<?php echo ( 1 < $this->page ) ? '' : ' disabled'; ?>" title="<?php _e( 'Go to the previous page' ); ?>" href="<?php echo $request_uri . ( $append ? '&' : '?' ) . 'pg' . '=' . max( $this->page - 1, 1 ); ?>">&lsaquo;</a>
<?php
			if ( true == $header ) {
?>
			<span class="paging-input"><input class="current-page" title="<?php _e( 'Current page' ); ?>" type="text" name="pg<?php echo $this->num; ?>" value="<?php echo $this->page; ?>" size="<?php echo strlen( $total_pages ); ?>"> <?php _e( 'of' ); ?> <span class="total-pages"><?php echo $total_pages; ?></span></span>
			<script>

				jQuery( document ).ready( function ( $ ) {
					var pageInput = $( 'input.current-page' );
					var currentPage = pageInput.val();
					pageInput.closest( 'form' ).submit( function ( e ) {
						if ( (1 > $( 'select[name="action"]' ).length || $( 'select[name="action"]' ).val() == -1) && (1 > $( 'select[name="action_bulk"]' ).length || $( 'select[name="action_bulk"]' ).val() == -1) && pageInput.val() == currentPage ) {
							pageInput.val( '1' );
        }
					} );
				} );
			</script>
<?php
           }
			else {
?>
			<span class="paging-input"><?php echo $this->page; ?> <?php _e( 'of' ); ?> <span class="total-pages"><?php echo number_format_i18n( $total_pages ); ?></span></span>
<?php
            }
?>
		<a class="next-page<?php echo ( $this->page < $total_pages ) ? '' : ' disabled'; ?>" title="<?php _e( 'Go to the next page' ); ?>" href="<?php echo $request_uri . ( $append ? '&' : '?' ) . 'pg' . '=' . min( $this->page + 1, $total_pages ); ?>">&rsaquo;</a>
		<a class="last-page<?php echo ( $this->page < $total_pages ) ? '' : ' disabled'; ?>" title="<?php _e( 'Go to the last page' ); ?>'" href="<?php echo $request_uri . ( $append ? '&' : '?' ) . 'pg' . '=' . $total_pages; ?>">&raquo;</a>
<?php
		}
    }

    function limit ($options=false)
    {
        $this->do_hook('limit',$options);
        if(isset($this->custom['limit'])&&function_exists("{$this->custom['limit']}"))
            return call_user_func( $this->custom['limit'],$this);
        if(false===$options||!is_array($options)||empty($options))
            $options = array(10,25,50,100,200);
        if(!in_array($this->limit,$options))
            $this->limit = $options[1];
        foreach($options as $option)
        {
            if($this->limit==$option)
                echo ' <span class="page-numbers current">'.$option.'</span>';
            else
                echo ' <a href="'.$this->var_update(array('limit'=>$option),array('order','order_dir','search_query')).'">'.$option.'</a>';
        }
    }
    function parse_template_string ($in,$row=false)
    {
        if($row!==false)
        {
            $this->temp_row = $this->row;
            $this->row = $row;
        }
        $out = preg_replace_callback("/({@(.*?)})/m", array($this, "parse_magic_tags"), $in);
        if($row!==false)
            $this->row = $this->temp_row;
        return $out;
    }
    function parse_magic_tags ($in)
    {
        $name = $in[2];
        $helper = '';
        if (false !== strpos($name, ',')) {
            list($name, $helper) = explode(',', $name);
            $name = trim($name);
            $helper = trim($helper);
        }
        $value = $this->row[$name];
        // Use helper if necessary
        if (!empty($helper)) {
            $value = $$helper($value,$name,$this->row);
        }
        return $value;
    }
}