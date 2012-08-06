<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_products extends Admin_Controller
{

	public $tabs	= array('description' => array('description'), 'shipping options' => array('shipping_weight', 'shipping_height', 'shipping_width', 'shipping_depth'));
	public $stream  = NULL;
	public $perpage = 30;
	public $section = 'products';

	public function __construct()
	{

		parent::__construct();

		// Load libraries, drivers & models
		$this->load->driver('Streams');
		$this->load->model('products_m');
		$this->load->model('categories_m');
		$this->load->model('streams_core/row_m');
		$this->load->library('streams_core/fields');
		$this->load->library('files/files');

		// Add metadata
		$this->template->append_css('module::products.css')
					   ->append_js('module::products.js')
					   ->append_metadata('<script type="text/javascript">' .
										 "\n  var currency = '" . $this->settings->get('currency') . "';" . 
										 "\n  var tax_rate = " . $this->settings->get('firesale_tax') . ";" .
										 "\n</script>");
	
		// Get the stream
		$this->stream = $this->streams->streams->get_stream('firesale_products', 'firesale_products');

	}

	public function index($type = 'na', $value = 'na', $start = 0)
	{

		// Get filter if set
		if( $type != 'na' AND $value != 'na' )
		{
			$filter   = array($type => $value);
			$products = $this->products_m->get_products($filter, $start, $this->perpage);
			$this->data->$type = $value;
		}
		else
		{
			$products = $this->products_m->get_products(array(), $start, $this->perpage);
		}

		// Build product data
		foreach( $products AS $key => $product )
		{
			$products[$key] = $this->products_m->get_product($product['id']);
		}
			
		// Assign variables
		$this->data->products 	= $products;
		$this->data->count		= count($this->products_m->get_products(( isset($filter) ? $filter : array() ), 0, 0)) OR 0;
		$this->data->pagination = create_pagination('/admin/firesale/products/' . ( $type != 'na' ? $type : 'na' ) . '/' . ( $value != 'na' ? $value : 'na' ) . '/', $this->data->count, $this->perpage, 6);
		$this->data->categories = array(0 => lang('firesale:label_filtersel')) + $this->categories_m->dropdown_values();

		// Build the page
		$this->template->title(lang('firesale:title') . ' ' . lang('firesale:sections:products'))
					   ->build('admin/products/index', $this->data);
	}
	
	public function create($id = NULL, $row = NULL)
	{

		// Check for post data
		if( $this->input->post('btnAction') == 'save' )
		{
			
			// Variables
			$input 	= $this->input->post();
			$skip	= array('btnAction');
			$extra 	= array(
						'return' 			=> '/admin/firesale/products/edit/-id-',
						'success_message'	=> lang('firesale:prod_' . ( $id == NULL ? 'add' : 'edit' ) . '_success'),
						'error_message'		=> lang('firesale:prod_' . ( $id == NULL ? 'add' : 'edit' ) . '_error')
					  );

			// Manually update categories
			if( $id !== NULL )
				$this->products_m->update_categories($id, $this->stream->id, $input['category']);
		
		}
		else
		{
			$input = FALSE;
			$skip  = array();
			$extra = array();
		}
	
		// Get the stream fields
		$fields = $this->fields->build_form($this->stream, ( $id == NULL ? 'new' : 'edit' ), ( $id == NULL ? $input : $row ), FALSE, FALSE, $skip, $extra);

		// Assign variables
		if( $row !== NULL ) { $this->data = $row; }
		$this->data->id		=  $id;
		$this->data->fields =  $this->products_m->fields_to_tabs($fields, $this->tabs);
		$this->data->tabs	=  array_reverse(array_keys($this->data->fields));
		
		// Get current images
		if( $row != FALSE )
		{
			$folder = $this->products_m->get_file_folder_by_slug($row->slug);
			$images = Files::folder_contents($folder->id);
			$this->data->images = $images['data']['file'];
		}
		
		// Add metadata
		$this->template->append_js('module::jquery.filedrop.js')
					   ->append_js('module::upload.js')
					   ->append_metadata($this->load->view('fragments/wysiwyg', NULL, TRUE));
	
		// Build the page
		$this->template->title(lang('firesale:title') . ' ' . lang('firesale:prod_title_' . ( $id == NULL ? 'create' : 'edit' )))
					   ->build('admin/products/create', $this->data);
	}
	
	public function edit($id)
	{
		
		// Get row
		if( $row = $this->row_m->get_row($id, $this->stream, FALSE) )
		{
			// Load form
			$this->create($id, $row);
		}
		else
		{
			$this->session->set_flashdata('error', lang('firesale:prod_not_found'));
			redirect('admin/firesale/products/create');
		}

	}
	
	public function delete($prod_id = null)
	{
	
		$delete   = true;
		$products = $this->input->post('action_to');

		if( $this->input->post('btnAction') == 'delete' )
		{
		
			for( $i = 0; $i < count($products); $i++ )
			{
			
				if( !$this->products_m->delete_product($products[$i]) )
				{
					$delete = false;
				}
			
			}
		
		}
		else if( $prod_id !== null )
		{
		
			if( !$this->products_m->delete_product($prod_id) )
			{
				$delete = false;
			}
		
		}
		
		if( $delete )
		{
			$this->session->set_flashdata('success', lang('firesale:prod_delete_success'));
		}
		else
		{
			$this->session->set_flashdata('error', lang('firesale:prod_delete_error'));
		}
		
		redirect('admin/firesale/products');
		
	}
	
	public function upload($id)
	{
	
		// Get product
		$row    = $this->row_m->get_row($id, $this->stream, FALSE);
		$folder = $this->products_m->get_file_folder_by_slug($row->slug);

		// Create folder?
		if( !$folder )
		{
			$parent = $this->products_m->get_file_folder_by_slug('product-images');
			$folder = Files::create_folder($parent->id, $row->title);
			$folder = (object)$folder['data'];
		}

		// Check for folder
		if( is_object($folder) )
		{

			// Upload it
			$status = Files::upload($folder->id);

			// Make square?
			$this->products_m->make_square($status);

			// Ajax status
			unset($status['data']);
			echo json_encode($status);
			exit;
		}

		// Seems it was unsuccessful
		echo json_encode(array('status' => FALSE, 'message' => 'Error uploading image'));
		exit();
	}
	
	public function ajax_quick_edit()
	{
		
		if( $this->input->is_ajax_request() )
		{

			$update = $this->products_m->update_product($this->input->post(), true);
	
			if( isset($update) && $update == TRUE )
			{
				$this->session->set_flashdata('success', lang('firesale:prod_edit_success'));
				echo 'ok';
			}
			else
			{
				echo lang('firesale:prod_edit_error');
			}

		}

	}

	public function ajax_product($id)
	{
		if( $this->input->is_ajax_request() )
		{
			echo json_encode($this->products_m->get_product($id));
			exit();
		}
	}

	public function ajax_order_images()
	{

		if( $this->input->is_ajax_request() )
		{

			$order = $this->input->post('order');

			if( strlen($order) > 0 )
			{
				$order = explode(',', $order);
				for( $i = 0; $i < count($order); $i++ )
				{
					$this->db->where('id', $order[$i])->update('files', array('sort' => $i));
				}
				echo 'ok';
				exit();
			}

		}

		echo 'error';
		exit();
	}
	
}
