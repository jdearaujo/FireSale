<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Product controller
 *
 * @author		Jamie Holdroyd
 * @author		Chris Harvey
 * @package		FireSale\Core\Controllers
 *
 */
class Front_product extends Public_Controller
{
    public function __construct()
    {

        parent::__construct();

        // Load libraries
        $this->load->driver('Streams');
        $this->lang->load('firesale');
        $this->load->model('categories_m');
        $this->load->model('products_m');
        $this->load->model('routes_m');
        $this->load->model('streams_core/row_m');
        $this->load->library('files/files');

        // Assign data object
        $this->data = new stdClass;

    }

    public function index($product)
    {

        // Get the product
        $product = $this->pyrocache->model('products_m', 'get_product', array($product), $this->firesale->cache_time);

        // Check it exists
        if ($product === false) {
            show_404();
        }

        // Product information
        $this->data->product  = $product;
        $this->data->category = $this->pyrocache->model('products_m', 'get_category', array($product), $this->firesale->cache_time);
        $this->data->images   = $this->pyrocache->model('products_m', 'get_images', array($product['slug']), $this->firesale->cache_time);
        $this->data->url      = $this->pyrocache->model('routes_m', 'build_url', array('product', $this->data->product['id']), $this->firesale->cache_time);
        $this->data->parent   = $this->products_m->build_breadcrumbs($this->data->category, $this->template);

        // Add page data
        $this->template->set_breadcrumb($this->data->product['title'], $this->data->url)
                       ->append_css('module::firesale.css')
                       ->append_js('module::firesale.js')
                       ->title($this->data->product['title'])
                       ->set($this->data);

        // Assign accessible information
        $this->template->design = 'product';
        $this->template->id     = $this->data->product['id'];

        // Fire events
        Events::trigger('product_viewed', array('id' => $product['id']));
        Events::trigger('page_build', $this->template);

        // Build page
        $view = ( isset($product['design']) && $product['design']['enabled'] == '1' ? $product['design']['view'] : 'product' );
        $this->template->build($view);
    }

    public function ajax_modifier_data()
    {

        // Check for data
        if ( $this->input->post() ) {

            // Get product data
            $data    = $this->pyrocache->model('modifier_m', 'cart_variation', array($this->input->post()), $this->firesale->cache_time);
            $product = $this->pyrocache->model('products_m', 'get_product', array($data['prd_code'][0], null, 1), $this->firesale->cache_time);

            // Build data for return
            $data = array(
                'code'            => $product['code'],
                'stock'           => $product['stock'],
                'stock_status'    => $product['stock_status'],
                'rrp_rounded'     => $product['rrp_rounded'],
                'rrp_formatted'   => $product['rrp_formatted'],
                'price_rounded'   => $product['price_rounded'],
                'price_formatted' => $product['price_formatted'],
                'diff_rounded'    => $product['diff_rounded'],
                'diff_formatted'  => $product['diff_formatted']
            );
            
            // Spit out data
            echo json_encode($data);
            exit();
        }

    }

}
