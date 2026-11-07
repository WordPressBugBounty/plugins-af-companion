<?php
// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}



class Af_companion_Menu
{

  private  $plugin_page;
  private $main;
  private $plugin_page_setup = array();
  private static $instance;
  public static function getInstance($main = null)
  {
    if (null === static::$instance) {

      static::$instance = new static($main);
    }


    return static::$instance;
  }

  protected function __construct($main = null)
  {
    $this->main = $main;
    add_action('admin_menu', array($this, 'aftc_create_plugin_page'));
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
  }

  public function aftc_create_plugin_page()
  {
    $this->plugin_page_setup = apply_filters('af-companion/plugin_page_setup', array(
      'parent_slug' => 'themes.php',
      'page_title' => esc_html__('AF Companion', 'af-companion'),
      'menu_title' => esc_html__('AF Companion', 'af-companion'),
      'capability' => 'import',
      'menu_slug' => 'af-companion',
    ));

    $aft_logo_svg = '<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
 width="405.000000pt" height="402.000000pt" viewBox="0 0 405.000000 402.000000"
 preserveAspectRatio="xMidYMid meet">
<g transform="translate(0.000000,402.000000) scale(0.100000,-0.100000)"
fill="#000000" stroke="none">
<path d="M1808 3775 c-332 -45 -651 -188 -917 -410 -292 -243 -511 -610 -590
-990 -36 -171 -51 -400 -33 -499 l7 -38 60 88 c110 163 224 268 378 349 271
143 598 149 880 17 119 -56 199 -113 292 -206 123 -124 207 -271 257 -454 21
-74 22 -104 27 -677 6 -644 5 -630 57 -676 55 -48 164 -65 222 -35 37 19 78
65 93 101 8 23 11 335 9 1261 -1 1116 0 1237 15 1295 76 292 370 460 664 379
40 -12 76 -18 80 -14 11 12 -152 155 -258 226 -171 114 -339 189 -546 243
-205 53 -483 69 -697 40z"/>
<path d="M2945 2964 c-45 -23 -76 -55 -96 -99 -17 -37 -19 -76 -19 -582 l0
-543 235 0 c256 0 272 -3 314 -56 28 -36 43 -96 35 -141 -9 -47 -66 -110 -112
-123 -21 -5 -133 -10 -249 -10 l-213 0 0 -505 0 -505 58 29 c32 16 99 56 148
90 418 279 693 746 755 1281 25 222 -2 517 -67 714 -46 139 -144 346 -165 346
-4 0 -3 -17 2 -37 5 -21 9 -65 9 -99 0 -57 -2 -63 -37 -97 -44 -45 -96 -61
-153 -48 -88 20 -119 61 -151 199 -17 78 -27 101 -57 134 -19 22 -48 46 -63
54 -37 19 -136 18 -174 -2z"/>
<path d="M1090 2059 c-233 -23 -449 -181 -541 -397 -51 -117 -66 -314 -34
-437 49 -191 182 -355 355 -438 112 -54 178 -69 304 -68 272 0 500 144 611
386 47 101 60 165 60 290 -1 187 -64 342 -190 470 -147 150 -341 216 -565 194z"/>
<path d="M1781 600 c-119 -89 -247 -148 -415 -190 l-109 -27 69 -32 c200 -94
544 -175 544 -129 0 21 -31 403 -33 411 -1 4 -26 -11 -56 -33z"/>
</g>
</svg>';

    $this->plugin_page = add_menu_page(
      $this->plugin_page_setup['page_title'],
      $this->plugin_page_setup['menu_title'],
      $this->plugin_page_setup['capability'],
      $this->plugin_page_setup['menu_slug'],
      apply_filters('af-companion/plugin_page_display_callback_function', array($this, 'display_plugin_page')),
      'data:image/svg+xml;base64,' . base64_encode($aft_logo_svg),
      33
    );

    add_submenu_page(
      'af-companion',
      esc_html__('Import Demo Website', 'af-companion'),
      esc_html__('Import Demo Site', 'af-companion'),
      'import',
      'af-companion',
      apply_filters('af-companion/plugin_page_display_callback_function', array($this, 'display_plugin_page'))
    );



   

    

    add_submenu_page(
      'af-companion',
      esc_html__('Premium Themes', 'af-companion'),
      esc_html__('Pro Themes', 'af-companion'),
      'manage_options',
      esc_url('https://afthemes.com/products/category/pro/')

    );

    // add_submenu_page(
    //   'af-companion',
    //   esc_html__('Plugins', 'af-companion'),
    //   esc_html__('Plugins', 'af-companion'),
    //   'manage_options',
    //   esc_url('https://afthemes.com/plugins/')

    // );


    // add_submenu_page(
    //   'af-companion',
    //   esc_html__('Videos', 'af-companion'),
    //   esc_html__('Videos', 'af-companion'),
    //   'manage_options',
    //   esc_url('https://www.youtube.com/channel/UCCJKF25c3HZpfaELha-86Hw/')

    // );

     add_submenu_page(
      'af-companion',
      esc_html__('Need help? Contact support team', 'af-companion'),
      esc_html__('Support', 'af-companion'),
      'manage_options',
      esc_url('https://afthemes.com/supports/')

    );

    // add_submenu_page(
    //   'af-companion',
    //   esc_html__('Blog', 'af-companion'),
    //   esc_html__('Blog', 'af-companion'),
    //   'manage_options',
    //   esc_url('https://afthemes.com/blog/')

    // );

    register_importer($this->plugin_page_setup['menu_slug'], $this->plugin_page_setup['page_title'], $this->plugin_page_setup['menu_title'], apply_filters('af-companion/plugin_page_display_callback_function', array($this, 'display_plugin_page')));
  }

  public function display_plugin_page()
  {

    $predefined_themes =  !empty($this->main->get_import_files())
      ? $this->main->get_import_files()
      : array();

    if (!empty($this->main->get_import_files()) && isset($_GET['import-mode']) && 'manual' === $_GET['import-mode']) {
      $predefined_themes = array();
    } ?>
<?php AF_Admin_Header::render( 'af-companion' ); ?>
    <div class="aftc  wrap  about-wrap">

      <?php ob_start();
      if (empty($predefined_themes)) : ?>

        <a href="https://afthemes.com" target="_blank">
          <img class="aftc__logo" src="<?php echo esc_url(AFTC_URL . '/assets/img/afthemes.png'); ?>" alt="<?php esc_html_e('AF themes', 'af-companion'); ?>" />
        </a>

        <h1 class="aftc__title"><?php esc_html_e('One Click Demo Import', 'af-companion'); ?></h1>
        <p class="aftc__description"><?php esc_html_e('Setup Your Site in a Single Click', 'af-companion'); ?></p>


      <?php
      endif;
      $plugin_title = ob_get_clean();

      // Start output buffer for displaying the plugin intro text.
      ob_start();

      ?>

      <div class="aftc__intro-notice  notice  notice-warning  is-dismissible">
        <p><?php esc_html_e('Before you begin, make sure all the required plugins are activated.', 'af-companion'); ?></p>
      </div>
      <?php if (empty($predefined_themes)) : ?>
        <div class="aftc__intro-text">

          <ul>
            <li>
              <h4 class="about-title"><?php esc_html_e('No other data will be deleted', 'af-companion'); ?></h4>
              <p><?php esc_html_e('No existing posts, pages, categories, images, custom post types or any other data will be deleted or modified.', 'af-companion'); ?></p>
            </li>
            <li>
              <h4 class="about-title"><?php esc_html_e('All demo settings will get imported', 'af-companion'); ?></h4>
              <p><?php esc_html_e('Posts, pages, images, widgets, menus and other theme settings will get imported.', 'af-companion'); ?></p>
            </li>
            <li>
              <h4 class="about-title"><?php esc_html_e('Ready in a couple of minutes', 'af-companion'); ?></h4>
              <p><?php esc_html_e('Please click on the Import button only once and wait, it can take a couple of minutes.', 'af-companion'); ?></p>
            </li>
          </ul>

        </div>

      <?php


      endif;
      $plugin_intro_text = ob_get_clean();

      // Display the plugin intro text (can be replaced with custom text through the filter below).
      echo wp_kses_post(apply_filters('af-companion/plugin_intro_text', $plugin_intro_text));
      ?>

      <?php if (empty($this->main->get_import_files())) : ?>
        <div class="notice  notice-info  is-dismissible">
          <p><?php esc_html_e('There are no predefined import files available in this theme. Please upload the import files manually!', 'af-companion'); ?></p>
        </div>
      <?php endif; ?>

      <?php if (empty($predefined_themes)) : ?>

        <div class="aftc__file-upload-container">
          <h2><?php esc_html_e('Manual demo files upload', 'af-companion'); ?></h2>

          <div class="aftc__file-upload">
            <h3>
              <label for="content-file-upload"><?php esc_html_e('Content import', 'af-companion'); ?></label>
            </h3>
            <p><?php esc_html_e('Upload a XML file', 'af-companion'); ?></p>
            <input id="aftc__content-file-upload" type="file" name="content-file-upload">
          </div>

          <div class="aftc__file-upload">
            <h3>
              <label for="widget-file-upload"><?php esc_html_e('Widget import', 'af-companion'); ?></label>
            </h3>
            <p><?php esc_html_e('Upload a WIE or JSON file', 'af-companion'); ?></p>
            <input id="aftc__widget-file-upload" type="file" name="widget-file-upload">
          </div>

          <div class="aftc__file-upload">
            <h3>
              <label for="customizer-file-upload"><?php esc_html_e('Customizer import', 'af-companion'); ?></label>
            </h3>
            <p><?php esc_html_e('Upload a DAT file', 'af-companion'); ?></p>
            <input id="aftc__customizer-file-upload" type="file" name="customizer-file-upload">
          </div>

          <?php if (class_exists('ReduxFramework')) : ?>
            <div class="aftc__file-upload">
              <h3>
                <label for="redux-file-upload"><?php esc_html_e('Choose a JSON file for Redux import:', 'af-companion'); ?></label>
              </h3>
              <input id="aftc__redux-file-upload" type="file" name="redux-file-upload">
              <div>
                <label for="redux-option-name" class="aftc__redux-option-name-label"><?php esc_html_e('Enter the Redux option name:', 'af-companion'); ?></label>
                <input id="aftc__redux-option-name" type="text" name="redux-option-name">
              </div>
            </div>
          <?php endif; ?>
        </div>

        <p class="aftc__button-container">
          <button class="aftc__button  button  button-hero  button-primary  js-aftc-import-data"><?php esc_html_e('Import Demo', 'af-companion'); ?></button>


        </p>
        <div>
          <?php if (!isset($_GET['import-mode']) || 'manual' !== $_GET['import-mode']) { ?>
            <div id="templatespare-template-collcetion-dashboard"></div>
          <?php } ?>

        </div>

      <?php else : ?>

        <?php ob_start() ?>

        <a href="https://afthemes.com" target="_blank">
          <img class="aftc__logo" src="<?php echo esc_url(AFTC_URL . '/assets/img/afthemes.png'); ?>" alt="<?php esc_html_e('AF themes', 'af-companion'); ?>" />
        </a>

        <h1 class="aftc__title"><?php esc_html_e('One Click Demo Import', 'af-companion'); ?></h1>
        <p class="aftc__description"><?php esc_html_e('Setup Your Site in a Single Click', 'af-companion'); ?></p>


        <?php

        $plugin_title = ob_get_clean();

        // Display the plugin title (can be replaced with custom title text through the filter below).
        echo wp_kses_post(apply_filters('af-companion/plugin_page_title', $plugin_title));

        // Display warrning if PHP safe mode is enabled, since we wont be able to change the max_execution_time.
        if (ini_get('safe_mode')) {
          printf(
            esc_html__('%sWarning: your server is using %sPHP safe mode%s. This means that you might experience server timeout errors.%s', 'af-companion'),
            '<div class="notice  notice-warning  is-dismissible"><p>',
            '<strong>',
            '</strong>',
            '</p></div>'
          );
        }

        ?>

        <div class="aftc__intro-notice  notice  notice-warning  is-dismissible">
          <p><?php esc_html_e('Before you begin, make sure all the required plugins are activated.', 'af-companion'); ?></p>
        </div>

        <div class="aftc__intro-text">

          <ul>
            <li>
              <h4 class="about-title"><?php esc_html_e('No other data will be deleted', 'af-companion'); ?></h4>
              <p><?php esc_html_e('No existing posts, pages, categories, images, custom post types or any other data will be deleted or modified.', 'af-companion'); ?></p>
            </li>
            <li>
              <h4 class="about-title"><?php esc_html_e('All demo settings will get imported', 'af-companion'); ?></h4>
              <p><?php esc_html_e('Posts, pages, images, widgets, menus and other theme settings will get imported.', 'af-companion'); ?></p>
            </li>
            <li>
              <h4 class="about-title"><?php esc_html_e('Ready in a couple of minutes', 'af-companion'); ?></h4>
              <p><?php esc_html_e('Please click on the Import button only once and wait, it can take a couple of minutes.', 'af-companion'); ?></p>
            </li>
          </ul>

        </div>

        <!-- $demo_json_url grid layout -->
        <?php af_companiion_display_starter_sites(); ?>

        <div id="js-aftc-modal-content"></div>

      <?php endif; ?>
      <p class="aftc__ajax-loader  js-aftc-ajax-loader">
        <span class="spinner"></span> <?php esc_html_e('Importing, please wait!', 'af-companion'); ?>
      </p>

      <div class="aftc__response  js-aftc-ajax-response"></div>
      <div class="aftc__intro-text">
        <?php
        if (!empty($this->main->get_import_files())) : ?>
          <?php if (empty($_GET['import-mode']) || 'manual' !== $_GET['import-mode']) : ?>
            <a href="<?php echo esc_url(add_query_arg(array('page' => $this->plugin_page_setup['menu_slug'], 'import-mode' => 'manual'), menu_page_url($this->plugin_page_setup['parent_slug'], false))); ?>" class="aftc__import-mode-switch"><?php esc_html_e('Switch to manual import!', 'af-companion'); ?></a>
            <a href="<?php echo esc_url(add_query_arg(array('page' => $this->plugin_page_setup['menu_slug']), menu_page_url($this->plugin_page_setup['parent_slug'], false))); ?>" class="aftc__import-mode-switch reload-importer" style="display: none;"><?php esc_html_e('Return to Importer', 'af-companion'); ?></a>
          <?php else : ?>
            <a href="<?php echo esc_url(add_query_arg(array('page' => $this->plugin_page_setup['menu_slug']), menu_page_url($this->plugin_page_setup['parent_slug'], false))); ?>" class="aftc__import-mode-switch"><?php esc_html_e('Switch back to theme predefined imports!', 'af-companion'); ?></a>


          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

<?php
  }

  public function admin_enqueue_scripts($hook)
  {

    // Enqueue the scripts only on the plugin page.
    if ($this->plugin_page === $hook) {
      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_style('wp-jquery-ui-dialog');

      wp_enqueue_script('aftc-main-js', AFTC_URL . 'assets/js/main.js', array('jquery', 'jquery-form'), AFTC_VERSION);
      wp_enqueue_script('aftc-activate-install', AFTC_URL . 'assets/js/activate-plugins.js', array('jquery', 'aftc-main-js'), AFTC_VERSION);

      $theme = wp_get_theme();
      wp_localize_script(
        'aftc-main-js',
        'aftc',
        array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('aftc-ajax-verification'),
          'import_files' => $this->main->get_import_files(),
          'wp_customize_on' => apply_filters('af-companion/enable_wp_customize_save_hooks', false),
          'import_popup' => apply_filters('af-companion/enable_grid_layout_import_popup_confirmation', true),
          'theme_screenshot' => $theme->get_screenshot(),
          'texts' => array(
            'missing_preview_image' => esc_html__('No preview image defined for this import.', 'af-companion'),
            'dialog_title' => esc_html__('Are you sure?', 'af-companion'),
            'dialog_no' => esc_html__('Cancel', 'af-companion'),
            'dialog_yes' => esc_html__('Yes, import!', 'af-companion'),
            'selected_import_title' => esc_html__('Selected demo for the import', 'af-companion'),
          ),
          'dialog_options' => apply_filters('af-companion/confirmation_dialog_options', array())
        )
      );

      wp_enqueue_style('aftc-main-css', AFTC_URL . 'assets/css/main.css', array(), AFTC_VERSION);
      
    }
    wp_enqueue_style('aftc-monitor-css', AFTC_URL . 'assets/css/monitor.css', array(), AFTC_VERSION);
    wp_enqueue_style('aftc-notice-css', AFTC_URL . 'assets/css/notice.css', array(), AFTC_VERSION);
  }
}
