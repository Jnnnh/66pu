<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Controller {

	/**
	 * 后台Admin class的构造器
	 *
	 * 载入条目、话题、类别模型
	 * inclue_login 载入session判断视图
	 * include_header 载入顶部统一视图
	 */
	function __construct()
		{
			parent::__construct();
			$this->load->model('M_item');
			$this->load->model('M_cat');
			$this->load->view('admin/include_login'); //检查cookie
		}

	/**
	 * 后台首页
	 *
	 */
	public function index()
	{
		$this->load->view('admin/include_header');
		$this->load->view('admin/index_view');
	}

	/**
	 * 登出
	 *
	 */
	public function logout()
	{
        $this->input->set_cookie('user_email','',0);
        $this->input->set_cookie('user_password','',0);
		//跳转
		Header("HTTP/1.1 303 See Other");
		Header("Location: ".site_url('login'));
		exit;
	}

	/**
	 * 搜索结果页
	 *
	 */
	public function search(){
        $this->load->model('M_webtaobao');
        $data['cat'] = $this->M_cat->get_all_cat();

         //获取搜索关键词
        $keyword = trim($this->input->get('keyword', TRUE),"'\"");

        /* cid是类别id */
        $cid = '0';
		$resp=$this->M_webtaobao->searchItem($keyword, $cid);

		$resp->total_results = $resp->getCount();
        $data['resp'] = $resp;
        $data['keyword'] =  $keyword;
//		var_dump($data['resp']->count);die;
		$this->load->view('admin/include_header');
		$this->load->view('admin/search_view',$data);
	}

	/**
	 * 关键词配置页
	 *
	 *
	 */
	public function keyword($operation = ''){
		$this->load->model('M_keyword');

		//新增关键词	
		if(!empty($operation) && $operation == 'add'){
			$keyword = $this->input->post('keyword');
			$this->M_keyword->add_new_keyword($keyword);
			$data['alert_info'] = '增加关键词成功！';

		}
		//删除关键词
		else if(!empty($operation) && $operation == 'delete'){
			$keyword = $this->input->post('keyword');
			$this->M_keyword->delete_keyword($keyword);
			$data['alert_info'] = '删除关键词'.$keyword.'成功！';
		}
		$data['keyword_list'] = $this->M_keyword->get_all_keyword();
		$this->load->view('admin/include_header');
		$this->load->view('admin/keyword_view',$data);
	}

	/**
	 * 统计页
	 *
	 * @param string stattype 可以是items/shops/cats
	 * @param integer offset 数据库偏移量
	 *
	 */
	public function status($stattype,$page = 1){
		//按条目
		if($stattype == 'items'){
			$limit = 40;
			$offset = ($page-1)*$limit;
			$this->load->library('pagination');

			$config['base_url'] = site_url('/admin/status/items');
			//site_url可以防止换域名代码错误。

	        $config['use_page_numbers'] = TRUE;
	        $config['first_url'] = site_url('/admin/status/items');

			$config['total_rows'] = $this->M_item->count_items();
			//这是模型里面的方法，获得总数。

			$config['per_page'] = $limit;
			$config['first_link'] = '首页';
			$config['last_link'] = '尾页';
			$config['num_links']=10;
			$config['uri_segment'] = 4;
			//上面是自定义文字以及左右的连接数

			$this->pagination->initialize($config);
			//初始化配置

			$data['pagination']=$this->pagination->create_links();
			//通过数组传递参数
			//以上是重点

			$data['query'] = $this->M_item->get_all_item($limit,$offset);
			$data['cat'] = $this->M_cat->get_all_cat();

			$this->load->view('admin/include_header');
			$this->load->view('admin/status/items_view',$data);
		}

		//如果是按店铺查看
		else if($stattype == 'shops'){
         	$data['query'] = $this->M_item->query_shops();
            $data['click_count_sum'] = $this->M_cat->click_count_by_cid();
            $data['item_count_sum'] = $this->M_item->count_items();
			$this->load->view('admin/include_header');
			$this->load->view('admin/status/shops_view',$data);
		}

		//如果是按类别查看
		else if($stattype == 'cats'){
			$data['query'] = $this->M_cat->query_cats();
			$data['click_count_sum'] = $this->M_cat->click_count_by_cid();
            $data['item_count_sum'] = $this->M_item->count_items();
            $this->load->view('admin/include_header');
			$this->load->view('admin/status/cats_view',$data);
		}
	}


	/**
	 * 管理类目
	 */
	public function cat(){
		$data['cat'] = $this->M_cat->get_all_cat();
		$data['cat_saved'] = false;
		$this->load->view('admin/include_header');
		$this->load->view('admin/cat_view',$data);
	}

    /**
     * 增加类目
     *
     * @param string $parentid 可选的参数
     */
	public function catadd($parentid = '0'){
        $this->load->model('M_webtaobao');
        $data['resp'] = $this->M_webtaobao->getCats($parentid);

		$this->load->view('admin/include_header');
		$this->load->view('admin/catadd_view',$data);
	}

	public function catupdate_op(){
		$this->M_cat->update_cat();
		$data['cat_saved'] = true;
        $data['cat'] = $this->M_cat->get_all_cat();
        $this->load->view('admin/include_header');
        $this->load->view('admin/cat_view',$data);
	}


	public function catadd_op(){
        $this->M_cat->add_cat();
        $data['cat'] = $this->M_cat->get_all_cat();
		$data['cat_saved'] = false;
        $this->load->view('admin/include_header');
        $this->load->view('admin/cat_view',$data);
	}

	/**
	 * 删除类目
	 *
	 */
	public function catdelete($cat_id){
		$this->M_cat->delete_cat($cat_id);
        $data['cat'] = $this->M_cat->get_all_cat();
		$data['cat_saved'] = true;
        $this->load->view('admin/include_header');
        $this->load->view('admin/cat_view',$data);
	}

	/**
	 * 删除条目
	 */
	public function delete_item(){
		$item_id = $_POST['item_id'];
		$this->M_item->delete_item($item_id);
	}

	/**
	 * 删除所有过期条目
	 */
	public function clear_expire(){

		$query = $this->M_item->get_all_item(99999, 0);
		$return_string = '成功删除下架商品：';
		foreach ($query->result() as $item) {
			$this->load->model('M_taobaoapi');
			$taobao_id = '';
	        if($item->num_iid){
	        	$taobao_id = $item->num_iid;
	        }
	        $item_id = $item->id;
	        $resp = $this->M_taobaoapi->getiteminfo($taobao_id);
	        if($resp && $resp->code){
	        	$this->M_item->delete_item($item_id);
	        	$return_string = $return_string.$item_id;
	        }
	        //$return_string = $resp;
		}
		$return_string = $return_string.'<a href="'.site_url('admin/status/items').'">返回</a>';
		print_r($return_string);
	}

    /**
     * 获得条目信息
     *
     * @return string $resp json字符串，包含所有的相关图片
     */
	public function getiteminfo(){
        $this->load->model('M_taobaoapi');
        $item_id = $_GET['item_id'];
        $resp = $this->M_taobaoapi->getiteminfo($item_id);

        $img_url_array =array();

        if($resp->item->item_imgs){
            foreach($resp->item->item_imgs->item_img as $item_img){
                array_push($img_url_array,(string)$item_img->url);
            }
        }

        if($resp->item->prop_imgs){
            foreach($resp->item->prop_imgs->prop_img as $prop_img){
                array_push($img_url_array,(string)$prop_img->url);
            }
        }

        $item_info_array = array();
        $item_info_array['imgs'] = $img_url_array;

        echo json_encode($item_info_array);
	}

	/**
	 * 设置条目信息
	 *
	 */
	public function setitem(){
		echo $this->M_item->set_item();
	}


}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */