<?php
// if(!isset($_REQUEST['appkey']) ||  $_REQUEST['appkey'] != md5('jamesonread20160810')){
// header('Content-Type: application/json; charset=utf-8');
// 	echo json_encode(array(
// 		'status'=>0,
// 		'error'=>'密钥错误',
// 	));
// 	exit;
// }
require_once './../config/config_ucenter.php';
require '../source/class/class_core.php';
C::app()->init();
if(!isset($_REQUEST['type']) || !isset($_REQUEST['module'])){
	echo json_encode(array(
		'status'=>0,
		'error'=>'参数错误',
	));
	exit;
}
$method = strtolower(trim($_REQUEST['type']).'_'.trim($_REQUEST['module']));
if(!method_exists('JosnData',$method)){
	echo json_encode(array(
		'status'=>0,
		'error'=>'参数错误，方法不存在',
	));
	exit;
}
$params = array();
if(isset($_REQUEST['param']) && !empty($_REQUEST['param'])){
	$getparams = explode(',',$_REQUEST['param']);
	foreach ($getparams as $key => $value) {
		$tmp = explode('----',$value);
		$params[$tmp[0]] = $tmp[1];
	}
	// print_r($params);
}

$json = new JosnData($params);
$json->$method();

/**
* 
*/
class JosnData
{
	protected $params;	
	protected $size = 10;
	protected $errnums = 0;
	function __construct($params=array())
	{
		$this->params = $params;
	}

	function get_index(){
		global $_G;
		loadcache('plugin');
		$books = DB::fetch_all("SELECT ".$this->_bookfield('c.category_name').",c.category_name FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.is_display=%d AND b.status=%d AND b.is_pdf=%d ORDER BY b.addtime DESC LIMIT %d",array('jamesonread_books','jamesonread_categorys',1,1,0,$this->size));
		foreach ($books as $key => $value) {
			$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
			$books[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
		}
		$shoujiadv = C::t('#jameson_read#jamesonread_topics')->fetch_by_type(66);
		$image = $url = $title = array();
		foreach ($shoujiadv as $key => $value) {
			if($value['image']){
				$image[] = $value['image'];
				$url[] = $value['url'];
				$title[] = diconv($value['adv'],CHARSET,'utf-8');
			}
		}
		$data = array(
			"status"=>1,
			"error"=>0,
			"flash"=>array(
				'images'=>$image,
				"url"=>$url,
				"title"=>$title,
			),
			"databook"=>$books
		);
		$this->_json($data);
	}
	public function get_morenstore()
	{
		$shujiahaoshu = C::t('#jameson_read#jamesonread_topics')->fetch_by_type(13);
		$tuijians = array();
		if(trim($shujiahaoshu[0]['adv'],',')){
			$book_ids = explode(',',trim($shujiahaoshu[0]['adv'],','));
			foreach ($book_ids as $key => $value) {
				if(intval($value)){
					$tuijians[] = C::t("#jameson_read#jamesonread_books")->fetch(intval($value));
				}
			}
		}
		foreach ($tuijians as $key => $value) {
			$tuijians[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$tuijians[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$tuijians[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$tuijians[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$tuijians[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$tuijians[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
		}
		$appgonggao = C::t('#jameson_read#jamesonread_topics')->fetch_by_type(44);
		foreach ($appgonggao as $key => $value) {
			if($value['image']){
				$appgonggao[$key]['image'] = diconv($value['image'],CHARSET,'utf-8');
			}else{
				unset($appgonggao[$key]);
			}
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'data'=>$tuijians,
			'gonggao'=>$appgonggao
		));
	}

	public function get_zhuanti()
	{
		$zhuantiid = intval($this->params['topic_id']);
		if($zhuantiid < 1){
			$zhuantilist = C::t('#jameson_read#jamesonread_topics')->fetch_by_type(77);
			foreach ($zhuantilist as $key => $value) {
				$zhuantilist[$key]['name'] = diconv($value['name'],CHARSET,'utf-8');
				$zhuantilist[$key]['descro'] = diconv($value['descro'],CHARSET,'utf-8');
			}
			// 具体某个专题
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'data'=>$zhuantilist
			));
		}else{
			// 专题列表
			$bookids = DB::result_first('SELECT adv FROM %t WHERE topic_id=%d',array('jamesonread_topics',$zhuantiid));
			$bookids = explode(',',trim($bookids));
			$books = DB::fetch_all("SELECT ".$this->_bookfield('c.category_name').",c.category_name FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.is_display=%d AND b.status=%d AND b.is_pdf=%d AND ".DB::field('book_id',$bookids,'in')." ORDER BY b.addtime DESC",array('jamesonread_books','jamesonread_categorys',1,1,0));
		}
		foreach ($books as $key => $value) {
			$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
		}
		if($books){
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'databook'=>$books,
			));
		}else{
			$this->_json($this->_error('没有了'));
		}
	}
	public function get_adv()
	{
		$adv = C::t('#jameson_read#jamesonread_topics')->fetch_by_type(55);
		$data = array();
		foreach ($adv as $key => $value) {
			if($value['image']){
				$value['adv'] = diconv($value['adv'],CHARSET,'utf-8');
				$data[] = $value;
			}
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'data'=>$tuijians,
			'adv'=>$data
		));
	}
	function get_jingxuan(){
		$total = DB::result_first("SELECT count(*) FROM %t WHERE is_display=%d AND status=%d AND is_pdf=%d",array('jamesonread_books',1,1,0));
		$total = ceil($total/$this->size);
		$current = (int) $this->params['current'];

		if($current >= $total){
			$this->_json($this->_error('没有了'));
		}else{
			$start = $current*$this->size;
			$books = DB::fetch_all("SELECT ".$this->_bookfield('c.category_name').",c.category_name FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.is_display=%d AND b.status=%d AND b.is_pdf=%d ORDER BY b.addtime DESC LIMIT %d,%d",array('jamesonread_books','jamesonread_categorys',1,1,0,$start,$this->size));
			foreach ($books as $key => $value) {
				$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
				$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
				$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
				$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
				$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
				$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
				$books[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
			}
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'total'=>$total,
				'databook'=>$books
			));
		}
	}

	public function get_changefensi()
	{
		$uid = intval($this->params['uid']);
		$bbsuid = intval($this->params['bbsuid']);
		$type = intval($this->params['type']);
		if($type){
			// 增加
			DB::insert('jamesonread_appguanzhu',array(
				'uid'=>$uid,
				'bbsuid'=>$bbsuid
			));
		}else{
			// 减少粉丝
			DB::delete('jamesonread_appguanzhu',"bbsuid='".$bbsuid."'",1);
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0
		));
	}
	private function _bookfield($field=''){
		$a = 'b.book_id,b.book_name,b.category_id,b.scores,b.author,b.uid,b.image,b.eximage,b.views,b.ordernum,b.desco,b.addtime,b.is_top,b.plan,b.favores,b.dpcount,b.bookmoney,b.status,b.is_jingxuan,b.zishu';
		if($field){
			return $a.','.$field;
		}
		return $a;
	}

	function get_store(){
		if($this->params['bbsuid']){
			$books = DB::fetch_all("SELECT ".$this->_bookfield('c.category_name')." FROM %t AS b INNER JOIN %t AS f ON b.book_id=f.book_id INNER JOIN %t AS c ON b.category_id=c.category_id WHERE f.uid=%d ORDER BY addtime DESC",array('jamesonread_books','jamesonread_favores','jamesonread_categorys',$this->params['bbsuid']));
			foreach ($books as $key => $value) {
				$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
				$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
				$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
				$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
				$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
				$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
				$books[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
			}
		}else{
			$books = array();
		}
		$this->_json(array(
				'status'=>1,
				'error'=>0,
				'bookstore'=>array('data'=>$books)
		));
	}

	function get_hasbuy()
	{
		$type = strtolower($this->params['buytype']);
		// if($type=='book'){
			// 图书
			$books = DB::fetch_all("SELECT ".$this->_bookfield('c.category_name')." FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id INNER JOIN %t AS h ON h.book_id=b.book_id WHERE h.buy_id=%d ORDER BY addtime DESC",array('jamesonread_books','jamesonread_categorys','jamesonread_buybooks',$this->params['bbsuid']));
			foreach ($books as $key => $value) {
				$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
				$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
				$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
				$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
				$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
				$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			}
			if($books){
				$this->_json(array(
					'status'=>1,
					'error'=>0,
					'data'=>$books
				));
			}else{
				$this->_json($this->_error('没有了'));
			}
		// }
	}
	function get_hasbuytid(){
		$bbsuid = (int) $this->params['bbsuid'];
		$data = DB::fetch_all('SELECT c.colum_id,c.tid,c.subject,c.zhangjie,c.price,c.uid,c.book_id,b.book_name FROM %t AS c INNER JOIN  %t AS b  ON c.book_id=b.book_id INNER JOIN %t AS h ON c.tid=h.tid WHERE h.buy_id=%d',array('jamesonread_colums','jamesonread_books','jamesonread_buytids',$bbsuid));
		foreach ($data as $key => $value) {
			$data[$key]['subject'] = diconv($value['subject'],CHARSET,'utf-8');
			$data[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
		}
		if($data){
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'data'=>$data
			));
		}else{
			$this->_json($this->_error('没有了'));
		}
	}
	function get_book()
	{
		if(!$this->params['book_id']){
			$this->_json($this->_error('图书不存在'));
		}
		$book_id = (int) $this->params['book_id'];
		$bbsuid = (int) $this->params['bbsuid'];
		$book = DB::fetch_first('SELECT '.$this->_bookfield('c.category_name').' FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.book_id=%d',array('jamesonread_books','jamesonread_categorys',$this->params['book_id']));
		if(!$book){
			$this->_json($this->_error('图书不存在'));
		}
		$book['book_name'] = diconv($book['book_name'],CHARSET,'utf-8');
		$book['category_name'] = diconv($book['category_name'],CHARSET,'utf-8');
		$book['desco'] = diconv($book['desco'],CHARSET,'utf-8');
		$book['author'] = diconv($book['author'],CHARSET,'utf-8');
		$book['avatar'] = avatar($book['uid'],'middle',true);
		$book['scores'] = round(2*$book['scores']/$book['dpcount'],1);
		$book['dashangnums'] = (int) DB::result_first('SELECT SUM(price) FROM %t WHERE book_id=%d',array('jamesonread_dashang',$this->params['book_id']));
		$book['dashangnums'] = ($book['dashangnums'] > 10000)?ceil($book['dashangnums']/1000).'K':$book['dashangnums'];
		$book['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$this->params['book_id']));
		$book['fensinums'] = (int) DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_appguanzhu',$book['uid']));
		$book['pinglunnums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_dianping',$this->params['book_id']));
		$book['pinglun'] = DB::fetch_all('SELECT d.dp_id,d.book_id,d.text,d.uid,d.addtime,d.fandui,d.zhichi,b.author FROM %t AS d INNER JOIN %t AS b ON d.book_id=b.book_id WHERE d.book_id=%d',array('jamesonread_dianping','jamesonread_books',$this->params['book_id']));
		foreach ($book['pinglun'] as $key => $value) {
			$book['pinglun'][$key]['avatar'] = avatar($value['uid'],'middle',true);
			$book['pinglun'][$key]['text'] = diconv($value['text'],CHARSET,'utf-8');
			$book['pinglun'][$key]['author'] = diconv(DB::result_first('SELECT username FROM %t WHERE uid=%d',array('common_member',$value['uid'])),CHARSET,'utf-8');
			$book['pinglun'][$key]['time'] = date('m/d',$value['addtime']);
		}
		$book['authorbooknums'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_books',$book['uid']));
		$book['authorinfo'] = DB::fetch_all('SELECT book_id,book_name,image,eximage FROM %t WHERE uid=%d ORDER BY addtime DESC LIMIT 3',array('jamesonread_books',$book['uid']));
		foreach ($book['authorinfo'] as $key => $value) {
			$book['authorinfo'][$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
		}
		$book['other'] = DB::fetch_all('SELECT book_id,book_name,image,eximage FROM %t WHERE status=%d AND is_jingxuan=%d LIMIT 3',array('jamesonread_books',1,1));
		foreach ($book['other'] as $key => $value) {
			$book['other'][$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
		}
		$firstjid = (int) DB::result_first('SELECT j_id FROM %t WHERE book_id=%d ORDER BY order_num ASC',array('jamesonread_fenjuan',$this->params['book_id']));
		
		$firstcid = DB::fetch_first('SELECT colum_id,tid,zhangjie,subject,price,uid FROM %t WHERE j_id=%d AND book_id=%d ORDER BY zhangjie ASC',array('jamesonread_colums',$firstjid,$this->params['book_id']));
		$book['status'] = 1;
		$book['error'] = 0;
		if($firstcid){
			$book['firstcolumid'] = $firstcid['colum_id'];
			$book['firstzhangjie'] = $firstcid['zhangjie'];
			$book['firstsubject'] = diconv($firstcid['subject'],CHARSET,'utf-8');
			$book['firstprice'] = (int) $firstcid['price'];
			if(($bbsuid == $book['uid']) || !$firstcid['price'] || DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d AND buy_id=%d',array('jamesonread_buybooks',$book_id,$bbsuid)) || DB::result_first('SELECT count(*) FROM %t WHERE tid=%d AND buy_id=%d',array('jamesonread_buytids',$firstcid['tid'],$bbsuid))){
				$book['firstkedu'] = 1;
			}else{
				$book['firstkedu'] = 0;
			}
		}else{
			$book['firstcolumid'] = $book['firstzhangjie'] = $book['firstprice'] = 0;
		}
		$this->_json($book);
	}
	public function get_shuping()
	{
		$total = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_dianping',intval($this->params['book_id'])));
		$data = array();
		$data['pinglunnums'] = $total;
		$total = ceil($total/$this->size);
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = ($this->size)*($this->params['current']-1);
		$data['bookinfo'] = DB::fetch_first('SELECT '.$this->_bookfield('c.category_name').' FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.book_id=%d',array('jamesonread_books','jamesonread_categorys',$this->params['book_id']));
		$data['bookinfo']['book_name'] = diconv($data['bookinfo']['book_name'],CHARSET,'utf-8');
		$data['bookinfo']['category_name'] = diconv($data['bookinfo']['category_name'],CHARSET,'utf-8');
		$data['bookinfo']['desco'] = cutstr(diconv($data['bookinfo']['desco'],CHARSET,'utf-8'),66);
		$data['bookinfo']['author'] = diconv($data['bookinfo']['author'],CHARSET,'utf-8');
		$data['bookinfo']['avatar'] = avatar($data['bookinfo']['uid'],'middle',true);
		$data['bookinfo']['scores'] = round(2*$data['bookinfo']['scores']/$data['bookinfo']['dpcount'],1);
		
		$data['pinglun'] = DB::fetch_all('SELECT * FROM %t WHERE book_id=%d ORDER BY addtime DESC LIMIT %d,%d',array('jamesonread_dianping',$this->params['book_id'],$start,$this->size));
		foreach ($data['pinglun'] as $key => $value) {
			$data['pinglun'][$key]['text'] = diconv($value['text'],CHARSET,'utf-8');
			$data['pinglun'][$key]['avatar'] = avatar($value['uid'],'middle',true);
			$data['pinglun'][$key]['author'] = diconv(DB::result_first('SELECT username FROM %t WHERE uid=%d',array('common_member',$value['uid'])),CHARSET,'utf-8');
			$data['pinglun'][$key]['time'] = date('m/d',$value['addtime']);
		}
		$data['status'] = 1;
		$data['error'] = 0;
		$this->_json($data);
	}
	public function get_addzorf()
	{
		$type  = addslashes($this->params['dptype']);
		$dp_id = intval($this->params['dp_id']);
		$res = DB::query('UPDATE '.DB::table('jamesonread_dianping').' SET `'.$type.'`=`'.$type."`+'1' WHERE dp_id='".$dp_id."'");
		if($res){
			$this->_json(array(
				'status'=>1,
				'error'=>0
			));
		}else{
			$this->_json($this->_error('失败'));
		}
	}
	function get_bookku(){
		$data = array();
		$data['nums']['booknums'] = DB::result_first('SELECT count(*) FROM %t',array('jamesonread_books'));
		$data['nums']['weeknums'] = DB::result_first('SELECT count(*) FROM %t WHERE addtime>%d',array('jamesonread_books',(time()-3600*24*7)));
		$data['categorys'] = DB::fetch_all('SELECT * FROM %t WHERE parent_id=%d',array('jamesonread_categorys',0));
		foreach ($data['categorys'] as $key => $value) {
			$data['categorys'][$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$data['categorys'][$key]['sub'] = DB::fetch_all('SELECT * FROM %t WHERE parent_id=%d',array('jamesonread_categorys',$value['category_id']));
			foreach ($data['categorys'][$key]['sub'] as $key2 => $value2) {
				$data['categorys'][$key]['sub'][$key2]['nums'] = DB::result_first('SELECT count(*) FROM %t WHERE category_id=%d',array('jamesonread_books',$value2['category_id']));
				$data['categorys'][$key]['sub'][$key2]['weeknums'] = DB::result_first('SELECT count(*) FROM %t WHERE category_id=%d AND addtime>%d',array('jamesonread_books',$value2['category_id'],(time()-3600*24*7)));
				$data['categorys'][$key]['sub'][$key2]['image'] = $value2['category_id'].".png";
				$data['categorys'][$key]['sub'][$key2]['category_name'] = diconv($value2['category_name'],CHARSET,'utf-8');
			}
		}
		$this->_json(array(
			"status"=>1,
			"error"=>0,
			"nums"=>$data['nums'],
			"categorys"=>$data['categorys']
		));
	}

	public function get_mulu()
	{
		if(!$this->params['book_id']){
			$this->_error('图书不存在');
		}
		$bookinfo = DB::fetch_first('SELECT * FROM %t WHERE book_id=%d',array('jamesonread_books',$this->params['book_id']));
		$bookinfo['desco'] = cutstr(diconv($bookinfo['desco'],CHARSET,'utf-8'),66);
		$mulu = DB::fetch_all('SELECT * FROM %t WHERE book_id=%d ORDER BY order_num ASC',array('jamesonread_fenjuan',$this->params['book_id']));
		foreach ($mulu as $key => $value) {
			$mulu[$key]['j_title'] = diconv($value['j_title'],CHARSET,'utf-8');
			$mulu[$key]['zhangjie'] = DB::fetch_all('SELECT * FROM %t WHERE j_id=%d ORDER BY zhangjie ASC',array('jamesonread_colums',$value['j_id']));
			foreach ($mulu[$key]['zhangjie'] as $key2 => $value2) {
				$mulu[$key]['zhangjie'][$key2]['subject'] = diconv($value2['subject'],CHARSET,'utf-8');
			}
		}
		$data = array(
			'status'=>1,
			'error'=>0,
			'book_id'=>$this->params['book_id'],
			'data'=>$mulu,
			'bookinfo'=>$bookinfo
		);
		$this->_json($data);
	}
	public function get_nextcolum()
	{
		$zhangjie = $this->params['zhangjie'];
		$colum_id = (int) $this->params['colum_id'];
		$book_id = (int) $this->params['book_id'];
		$bbsuid = (int) $this->params['bbsuid'];
		$next = DB::fetch_first('SELECT uid,colum_id,zhangjie,subject,tid,uid,price FROM %t WHERE book_id=%d AND '.DB::field('zhangjie',$zhangjie,'>=').' AND colum_id !=%d ORDER BY zhangjie ASC',array("jamesonread_colums",$book_id,$colum_id));
		$next['subject'] = diconv($next['subject'],CHARSET,'utf-8');
		// 判断是否可读
		if(($bbsuid == $next['uid']) || !$next['price'] || DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d AND buy_id=%d',array('jamesonread_buybooks',$book_id,$bbsuid)) || DB::result_first('SELECT count(*) FROM %t WHERE tid=%d AND buy_id=%d',array('jamesonread_buytids',$next['tid'],$bbsuid))){
			$next['kedu'] = 1;
		}else{
			$next['kedu'] = 0;
		}
		$this->_json(array(
				'status'=>1,
				'error'=>0,
				'data'=>$next
		));	
	}
	public function get_prevcolum()
	{
		$zhangjie = $this->params['zhangjie'];
		$colum_id = (int) $this->params['colum_id'];
		$book_id = (int) $this->params['book_id'];
		$bbsuid = (int) $this->params['bbsuid'];
		$prev = DB::fetch_first('SELECT colum_id,zhangjie,subject,tid,price,uid FROM %t WHERE book_id=%d AND '.DB::field('zhangjie',$zhangjie,'<=').' AND colum_id !=%d ORDER BY zhangjie DESC',array("jamesonread_colums",$book_id,$colum_id));
		$prev['subject'] = diconv($prev['subject'],CHARSET,'utf-8');
		if(($bbsuid == $prev['uid']) || !$prev['price'] || DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d AND buy_id=%d',array('jamesonread_buybooks',$book_id,$bbsuid)) || DB::result_first('SELECT count(*) FROM %t WHERE tid=%d AND buy_id=%d',array('jamesonread_buytids',$prev['tid'],$bbsuid))){
			$prev['kedu'] = 1;
		}else{
			$prev['kedu'] = 0;
		}
		$this->_json(array(
				'status'=>1,
				'error'=>0,
				'data'=>$prev
		));	
	}
	public function get_downbook()
	{
		// echo $this->params['book_id']; 
		if(!$this->params['book_id']){
			$this->_error('图书不存在');
		}
		$book_id = intval($this->params['book_id']);
		$colum_id = intval($this->params['colum_id']);
		$filename = './txt/'.$book_id.'_'.$colum_id.'.txt';
		file_put_contents($filename, '');
		// 所有章节
		$pid = DB::fetch_first('SELECT pid,subject FROM %t WHERE book_id=%d AND colum_id=%d',array('jamesonread_colums',$book_id,$colum_id));
		if(!$pid){
			echo '';
			exit;
		}
		// 处理messag
		$message = DB::result_first('SELECT message FROM %t WHERE pid=%d',array('forum_post',$pid['pid']));
		$message = diconv(trim($message),CHARSET,'utf-8');
		$message = strip_tags($message);
		$tmp = preg_replace(array("/\[.*\]\d+[\/.*]/i","/\[.*\]/i","/jameson_read/i","/\s{5,30}/i"),array('','','',"\r\n"),$message);
		file_put_contents($filename,diconv(trim($pid['subject']),CHARSET,'utf-8')."\r\n".$tmp."\r\n");
		header("Content-Type: application/force-download,charset=utf-8");
		header("Content-Disposition: attachment; filename=".basename($filename));
		readfile($filename);
	}

	private function _count_search($keyword){
		$contind = DB::field('book_name','%'.$keyword.'%','like');
		return (int) DB::result_first("SELECT count(*) FROM ".DB::table('jamesonread_books')." WHERE ".$contind);
	}
	public function get_search()
	{
		$keyword = ($this->params['text']);
		$keyword = preg_replace("/(%|_)+/i", '\\\\'."\\1", addslashes($keyword));
		$keyword = diconv($keyword,'utf-8',CHARSET);
		$total = $this->_count_search($keyword);
		$total = ceil($total/$this->size);
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = ($this->size)*($this->params['current']-1);
		$contind = DB::field('book_name','%'.$keyword.'%','like');
		$book = DB::fetch_all("SELECT ".$this->_bookfield('c.category_name')." FROM ".DB::table('jamesonread_books')." AS b INNER JOIN ".DB::table('jamesonread_categorys')." AS c ON b.category_id=c.category_id WHERE $contind  LIMIT ".$start.",".$this->size);
		foreach ($book as $key => $value) {
			$book[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$book[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$book[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$book[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$book[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$book[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
			$book[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'total'=>$total,
			'bookstore'=>$book
		));
	}
	public function get_paihang()
	{
		$paihangbang = array();
		// 阅读榜
		$paihangbang[0]['data'] = C::t('#jameson_read#jamesonread_books')->fetch_order_bypid('views','addtime',0,4,0);
		$paihangbang[0]['image']='./image/yuedubang.png';
		$paihangbang[0]['phname']="阅读榜";
		$paihangbang[0]['id']=1;
		foreach ($paihangbang[0]['data'] as $key => $value) {
			$paihangbang[0]['data'][$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$paihangbang[0]['data'][$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$paihangbang[0]['data'][$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$paihangbang[0]['data'][$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
		}
		// 好评榜
		$paihangbang[1]['data'] = C::t('#jameson_read#jamesonread_books')->fetch_order_bypid('scores','views',0,4,0);
		$paihangbang[1]['image']='./image/haopingbang.png';
		$paihangbang[1]['phname']="好评榜";
		$paihangbang[1]['id']=2;
		foreach ($paihangbang[1]['data'] as $key => $value) {
			$paihangbang[1]['data'][$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$paihangbang[1]['data'][$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$paihangbang[1]['data'][$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$paihangbang[1]['data'][$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
		}
		// 收藏榜
		$paihangbang[2]['data'] = C::t('#jameson_read#jamesonread_books')->fetch_order_bypid('favores','views',0,4,0);
		$paihangbang[2]['image']='./image/shoucangbang.png';
		$paihangbang[2]['phname']="收藏榜";
		$paihangbang[2]['id']=3;
		foreach ($paihangbang[2]['data'] as $key => $value) {
			$paihangbang[2]['data'][$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$paihangbang[2]['data'][$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$paihangbang[2]['data'][$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$paihangbang[2]['data'][$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
		}
		// 编辑精选榜
		$paihangbang[3]['data'] = C::t('#jameson_read#jamesonread_books')->fetch_field_bypid('is_jingxuan',1,'=',0,4,0);
		$paihangbang[3]['image']='./image/jingxuanbang.png';
		$paihangbang[3]['phname']="精选榜";
		$paihangbang[3]['id']=4;
		foreach ($paihangbang[3]['data'] as $key => $value) {
			$paihangbang[3]['data'][$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$paihangbang[3]['data'][$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$paihangbang[3]['data'][$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$paihangbang[3]['data'][$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
		}
		$data = array(
			"status"=>1,
			"error"=>0,
			"phdata"=>$paihangbang
		);
		$this->_json($data);
	}

	function get_paihanginfo(){
		$total = 10;
		$start = intval($this->params['current']-1)*$this->size;
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$order = '';
		switch ($this->params['paihangid']) {
			case 1:
				$order = 'views';
				break;
			case 2:
				$order = 'scores';
				break;
			case 3:
				$order = 'favores';
				break;
			case 4:
				$order = 'is_jingxuan';
		}
		$books = DB::fetch_all('SELECT '.$this->_bookfield('c.category_name').' FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.status=%d ORDER BY '.$order.' DESC LIMIT %d,%d',array('jamesonread_books','jamesonread_categorys',1,$start,$this->size));
		foreach ($books as $key => $value) {
			$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$books[$key]['num'] = ($key+1)+$start;
			$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
			$books[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
		}
		$this->_json(array(
				'status'=>1,
				'error'=>0,
				'total'=>$total,
				'databook'=>$books
			));
	}
	function get_viporfree(){
		$btype = intval($this->params['btype']);
		$con = $btype?DB::field('bookmoney',0,'>'):DB::field('bookmoney',0);
		$total = DB::result_first('SELECT count(*) FROM %t WHERE '.$con.' AND status=%d',array('jamesonread_books',1));
		$total = ceil($total/$this->size);
		$start = intval($this->params['current']-1)*$this->size;
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$books = DB::fetch_all('SELECT '.$this->_bookfield('c.category_name').' FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.status=%d AND b.'.$con.' ORDER BY b.addtime DESC LIMIT %d,%d',array('jamesonread_books','jamesonread_categorys',1,$start,$this->size));
		foreach ($books as $key => $value) {
			$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
			$books[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
		}
		$this->_json(array(
				'status'=>1,
				'error'=>0,
				'total'=>$total,
				'databook'=>$books
			));
	}
	function get_category(){
		$category_id = intval($this->params['category_id']);
		$total = DB::result_first('SELECT count(*) FROM %t WHERE status=%d AND  category_id=%d',array('jamesonread_books',1,$category_id));
		$total = ceil($total/$this->size);
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = intval($this->params['current']-1)*$this->size;
		$books = DB::fetch_all('SELECT '.$this->_bookfield('c.category_name').' FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.status=%d AND b.category_id=%d ORDER BY addtime DESC LIMIT %d,%d',array('jamesonread_books','jamesonread_categorys',1,$category_id,$start,$this->size));
		foreach ($books as $key => $value) {
			$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
			$books[$key]['colums'] = DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_colums',$value['book_id']));
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'total'=>$total,
			'databook'=>$books
		));
	}
	function get_authornewbook(){
		$uid = intval($this->params['authorid']);
		$books = DB::fetch_all('SELECT '.$this->_bookfield('c.category_name').' FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.status=%d AND b.addtime > %d ORDER BY addtime DESC ',array('jamesonread_books','jamesonread_categorys',1,time()-3600*24*7));
		foreach ($books as $key => $value) {
			$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'total'=>$total,
			'databook'=>$books
		));
	}
	function get_fensilist(){
		$authorid = intval($this->params['authorid']);
		$total = (int) DB::result_first('SELECT count(*) FROM %t WHERE uid=%d AND bbsuid >%d',array('jamesonread_appguanzhu',$authorid,0));
		$total = ceil($total/$this->size);//页数
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = intval($this->params['current']-1)*$this->size;
		$data = DB::fetch_all('SELECT c.uid,c.username FROM %t AS c INNER JOIN %t AS a ON a.bbsuid=c.uid WHERE a.uid=%d AND a.bbsuid>%d  LIMIT %d,%d',array('common_member','jamesonread_appguanzhu',$authorid,0,$start,$this->size));
		foreach ($data as $key => $value) {
			$data[$key]['username'] = diconv($value['username'],CHARSET,'utf-8');
			$data[$key]['avatar'] = avatar($value['uid'],'middle',true);
		}
		$this->_json(array(
			'total'=>$total,
			'status'=>1,
			'error'=>0,
			'datafensi'=>$data
		));
	}
		function get_dashanglist(){
		$book_id = intval($this->params['book_id']);
		$total = (int) DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d',array('jamesonread_dashang',$book_id));
		$total = ceil($total/$this->size);//页数
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = intval($this->params['current']-1)*$this->size;
		$data = DB::fetch_all('SELECT * FROM %t WHERE book_id=%d ORDER BY addtime DESC LIMIT %d,%d',array('jamesonread_dashang',$book_id,$start,$this->size));
		foreach ($data as $key => $value) {
			$data[$key]['username'] = diconv($value['username'],CHARSET,'utf-8');
			$data[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$data[$key]['time'] = date('m/d',$value['addtime']);
		}
		$this->_json(array(
			'total'=>$total,
			'status'=>1,
			'error'=>0,
			'data'=>$data
		));
	}

public function get_dashangaction()
{
	global $_G;
	loadcache('plugin');
	require '../source/plugin/jameson_read/function/function_jameson.php';
	init_jameson();
	$uid = (int) $this->params['bbsuid'];
	$authorid = (int) $this->params['authorid'];
	$price = (int) $this->params['dashangshuliang'];
	$dqdstramenums = intval(DB::result_first("SELECT %i  FROM %t WHERE uid=%d",array('extcredits'.$_G['jameson_read']['dstrameid'],'common_member_count',$uid)));
	if($dqdstramenums < $price){
		$this->_json($this->_error($_G['jameson_read']['dstrametitle'].'不足，请先充值'));
	}
	$book_id = (int) $this->params['book_id'];
	$yongjinbi = $_G['jameson_read']['yongjin'];
	$dyongjin = floor($price*$yongjinbi/100);
	$trameid = $_G['jameson_read']['dstrameid'];
	// 打赏者减少
	$res1 = updatemembercount($uid,array($trameid=>-$price),true,'jds',0,'打赏图书费用','打赏图书费用');
		// amdin收取佣金
	$res2 = updatemembercount(1,array($trameid=>$dyongjin),true,'Y_J',0,'佣金收入','佣金收入');
		// 作者的收入
	$res3 = updatemembercount($authorid,array($trameid=>$price-$dyongjin),true,'jbd',0,'打赏收入','打赏收入');
	if(!$res1 && !$res2 && !$res3){
		C::t('#jameson_read#jamesonread_dashang')->insert(array(
			'uid'=>$uid,
			'authorid'=>$authorid,
			'addtime'=>time(),
			'price'=>$price,
			'book_id'=>$book_id,
			'tid'=>0,
			'pid'=>0,
			'username'=>DB::result_first('SELECT username FROM %t WHERE uid=%d',array('common_member',$uid))
			));
		C::t('#jameson_read#jamesonread_yongjin')->insert(array(
			'buy_id'=>$uid,
			'author_id'=>$authorid,
			'book_id'=>$book_id,
			'saleprice'=>$price,
			'yongjinbi'=>$yongjinbi,
			'yongjin'=>$dyongjin,
			'addtime'=>time(),
			'type'=>2
		));
		$this->_json(array(
			'status'=>1,
			'error'=>0
		));
	}else{
		$this->_json($this->_error('打赏出错了'));
	}
}
public function get_buytid()
{
	global $_G;
	loadcache('plugin');
	require '../source/plugin/jameson_read/function/function_jameson.php';
	init_jameson();
	$uid = (int) $this->params['bbsuid'];
	$authorid = (int) $this->params['authorid'];
	$dqtramenums = intval(DB::result_first("SELECT %i  FROM %t WHERE uid=%d",array('extcredits'.$_G['jameson_read']['trameid'],'common_member_count',$uid)));
	$price = (int) $this->params['price'];
	if($dqtramenums < $price){
		$this->_json($this->_error($_G['jameson_read']['trametitle'].'不足，请先充值'));
	}
	$book_id = (int) $this->params['book_id'];
	$colum_id = (int) $this->params['colum_id'];
	$tid = DB::fetch_first('SELECT * FROM %t WHERE colum_id=%d',array('jamesonread_colums',$colum_id));
	$yongjinbi = $_G['jameson_read']['yongjin'];
	$buyyongjin = floor($price*$yongjinbi/100);
	$trameid = $_G['jameson_read']['trameid'];
	// 购买者减去 price
	$res1 = updatemembercount($uid,
		array($trameid=>-$price),
		true,'jgm',$tid['tid'],'购买图书费用',$tid['subject']);
	// 作者增加 price-buyyongji
	$res2 = updatemembercount($authorid,
		array($trameid=>($price-$buyyongjin)),
		true,'jxs',$tid['tid'],'销售图书费用',$tid['subject']);
	// admin增加 buyyongji
	$res3 = updatemembercount(1,
		array($trameid=>$buyyongjin),
		true,'Y_J',0,'销售图书费用',$tid['subject']);
	if(!$res1 && !$res2 && !$res3){
		
		C::t('#jameson_read#jamesonread_yongjin')->insert(array(
			'buy_id'=>$uid,
			'author_id'=>$authorid,
			'book_id'=>$book_id,
			'saleprice'=>$price,
			'yongjinbi'=>$yongjinbi,
			'yongjin'=>$buyyongjin,
			'addtime'=>time(),
			'type'=>1
			));
		C::t('#jameson_read#jamesonread_buytids')->insert(array(
			'buy_id'=>$uid,
			'author_id'=>$authorid,
			'tid'=>$tid['tid'],
			'category_id'=>$tid['category_id'],
			'addtime'=>time(),
			'price'=>$price,
			'book_id'=>$book_id
		),true);
		$this->_json(array(
			'status'=>1,
			'error'=>0
		));
	}else{
		$this->_json($this->_error('购买出错了'));
	}
}

public function get_isbuy()
{
	$book_id = (int) $this->params['book_id'];
	$bbsuid = (int) $this->params['bbsuid'];
	$colum_id = (int) $this->params['colum_id'];
	$tid = DB::fetch_first('SELECT * FROM %t WHERE colum_id=%d',array('jamesonread_colums',$colum_id));
	if(($tid['uid']==$bbsuid) || DB::result_first('SELECT count(*) FROM %t WHERE book_id=%d AND buy_id=%d',array('jamesonread_buybooks',$book_id,$bbsuid)) || DB::result_first('SELECT count(*) FROM %t WHERE tid=%d AND buy_id=%d',array('jamesonread_buytids',$tid['tid'],$bbsuid))){
		$this->_json(array(
			'status'=>1,
			'error'=>0
		));
	}else{
		$this->_json($this->_error('没有购买'));	
	}
	# code...
}
public function get_myguanzhu()
{
	$uid = (int) $this->params['uid'];
	if($uid){
		$total = (int) DB::result_first('SELECT count(*) FROM %t WHERE bbsuid=%d',array('jamesonread_appguanzhu',$uid));
		$total = ceil($total/$this->size);//页数
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = intval($this->params['current']-1)*$this->size;
		$data = DB::fetch_all('SELECT uid FROM %t WHERE bbsuid=%d',array('jamesonread_appguanzhu',$uid));
		foreach ($data as $key => $value) {
			$data[$key]['author'] = diconv(DB::result_first('SELECT author FROM %t WHERE uid=%d',array('jamesonread_books',$value['uid'])),CHARSET,'utf-8');
			$data[$key]['tushunums'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_books',$value['uid']));
			$data[$key]['fensinums'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_appguanzhu',$value['uid']));
			$data[$key]['avatar'] = avatar($value['uid'],'middle',true);
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'total'=>$total,
			'data'=>$data
		));
	}else{
		$authors = trim($this->params['local']);
		if(!$authors){
			$this->_json($this->_error('没有了'));
		}
		$authors = explode('__', $authors);
		$data = DB::fetch_all('SELECT author,uid,count(book_id) as tushunums FROM %t WHERE '.DB::field('uid',$authors,'in').' GROUP BY uid',array('jamesonread_books'));
		foreach ($data as $key => $value) {
			$data[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$data[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$data[$key]['fensinums'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_appguanzhu',$value['uid']));
		}
		$this->_json(array(
			'total'=>1,
			'status'=>1,
			'error'=>0,
			'data'=>$data
		));
	}
}
	public function get_hebingguanzhu()
	{
		$bbsuid = intval($this->params['bbsuid']);
		$local = trim($this->params['local']);
		if($bbsuid && $local && ($authorids = explode('__',$local))){
			foreach ($authorids as $key => $value) {
				if(!DB::result_first('SELECT count(*) FROM %t WHERE uid=%d AND bbsuid=%d',array('jamesonread_appguanzhu',$value,$bbsuid))){
					DB::insert('jamesonread_appguanzhu',array(
						'bbsuid'=>$bbsuid,
						'uid'=>$value
					));
				}
			}
			// 同步会客户端关注信息
			$hasguanzhu = DB::fetch_all('SELECT uid FROM %t WHERE bbsuid=%d',array('jamesonread_appguanzhu',$bbsuid));
			$data = array();
			foreach ($hasguanzhu as $key => $value) {
				$data[] = $value['uid'];
			}
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'data'=>$data
			));
		}else{
			$this->_json($this->_error('没有了'));
		}
	}
	function get_author(){
		$uid = $this->params['uid'];
		$data = array();
		$data['allnums'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_books',$uid));
		$data['newnums'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d AND addtime>%d',array('jamesonread_books',$uid,(time()-3600*24*7)));
		$data['authorid'] = $uid;
		$data['avatar'] = avatar($uid,'middle',true);
		$data['author'] = DB::result_first('SELECT author FROM %t WHERE uid=%d LIMIT %d',array('jamesonread_books',$uid,1));
		$data['author'] = diconv($data['author'],CHARSET,'utf-8');
		$data['dashang'] = (int) DB::result_first('SELECT SUM(price) FROM %t WHERE authorid=%d',array('jamesonread_dashang',$uid));
		$tmp = DB::fetch_all('SELECT book_id FROM %t WHERE uid=%d',array('jamesonread_books',$uid));
		$bookids = array();
		foreach ($tmp as $key => $value) {
			$bookids[] = $value['book_id'];
		}
		$data['fensi'] = DB::result_first('SELECT count(*) FROM %t WHERE '.DB::field('uid',$uid),array('jamesonread_appguanzhu'));
		$data['fensi'] = $data['fensi']>10000 ? floor($data['fensi']/1000).'K':$data['fensi']; 
		$data['dashang'] = $data['dashang']>10000 ? floor($data['dashang']/1000).'K':$data['dashang']; 
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'authorinfo'=>$data
		));
	}
	function get_authorbooks(){
		$uid=$this->params['uid'];
		$total = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d AND status=%d',array('jamesonread_books',$uid,1));
		$total = ceil($total/$this->size);
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = $this->size*(intval($this->params['current'])-1);
		$books = DB::fetch_all("SELECT ".$this->_bookfield('c.category_name').' FROM %t AS b INNER JOIN %t AS c ON b.category_id=c.category_id WHERE b.uid=%d AND b.status=%d ORDER BY addtime DESC LIMIT %d,%d',array('jamesonread_books','jamesonread_categorys',$uid,1,$start,$this->size));
		foreach ($books as $key => $value) {
			$books[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$books[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$books[$key]['category_name'] = diconv($value['category_name'],CHARSET,'utf-8');
			$books[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$books[$key]['scores'] = round(2*$value['scores']/$value['dpcount'],1);
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'total'=>$total,
			'databook'=>$books
		));
	}
	function get_user(){
		$uid = $this->params['bbsuid'];
		global $_G;
		loadcache('plugin');
		require '../source/plugin/jameson_read/function/function_jameson.php';
		init_jameson();
		$data = array();
		$data['trameid'] = $_G['jameson_read']['trameid'];
		$data['trametitle'] = diconv($_G['jameson_read']['trametitle'],CHARSET,'utf-8');
		$data['tramefield'] = 	'extcredits'.$data['trameid'];
		$data['tramenums'] = intval(DB::result_first("SELECT %i  FROM %t WHERE uid=%d",array($data['tramefield'],'common_member_count',$uid)));

		$data['dstrameid'] = $_G['jameson_read']['dstrameid'];
		$data['dstrametitle'] = diconv($_G['jameson_read']['dstrametitle'],CHARSET,'utf-8');
		$data['dstramefield'] = 'extcredits'.$data['dstrameid'];
		$data['dstramenums'] = intval(DB::result_first("SELECT %i  FROM %t WHERE uid=%d",array($data['dstramefield'],'common_member_count',$uid)));
		$data['username'] = diconv(DB::result_first("SELECT username  FROM %t WHERE uid=%d",array('common_member',$uid)),CHARSET,'utf-8');
		$data['avatar'] = avatar($uid,'middle',true);
		$data['uid'] = $uid;
		$data['bbsuid'] = $uid;
		$data['status'] = 1;
		$data['error'] = 0;
		$data['shoucang'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_favores',$uid));
		$this->_json($data);
	}
	public function get_init()
	{
		global $_G;
		loadcache('plugin');
		require '../source/plugin/jameson_read/function/function_jameson.php';
		init_jameson();
		$data = array();
		$data['trameid'] = $_G['jameson_read']['trameid'];
		$data['trametitle'] = $_G['jameson_read']['trametitle'];
		$data['tramefield'] = $_G['jameson_read']['tramefield'];
		$data['dstrameid'] = $_G['jameson_read']['dstrameid'];
		$data['dstrametitle'] = $_G['jameson_read']['dstrametitle'];
		$data['dstramefield'] = $_G['jameson_read']['dstramefield'];
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'data'=>$data
		));
	}
	// 使用论坛帐号的登录验证
	public function get_login()
	{
		$user = ($this->params['username']);
		$user = addslashes(diconv($user,'utf-8',CHARSET));
		$pass = addslashes((trim($this->params['pass'])));
		$pass = substr($pass,6,-6);
		$info = DB::fetch_first("SELECT * FROM ".DB::table('ucenter_members')."  WHERE username='".$user."'");
		$spass = md5(md5($pass).$info['salt']);
		$logintype = strtolower($this->params['logintype']);
		if($spass == $info['password']){
			$data = $this->_getuserinfo($info['uid']);
			$data['name'] = diconv($info['username'],CHARSET,'utf-8');
			// 绑定
			if(isset($this->params['loginaction']) && ($this->params['loginaction'] == 'bind')){
				$binddata = array();
				$binddata['uid'] = $info['uid'];
				$binddata[$logintype.'openid'] = $this->params['openid'];
				$binddata[$logintype.'name']= addslashes(($this->params['nickname']));
				if($upid = DB::result_first('SELECT id FROM %t WHERE uid=%d',array('jamesonread_appuser',$info['uid']))){
					C::t('#jameson_read#jamesonread_appuser')->update($upid,$binddata);
				}else{
					C::t('#jameson_read#jamesonread_appuser')->insert($binddata);
				}
			}else{
				$data['dqlogintype'] = 'forum';
			}
			// 判断是否绑定qq或者微信
			$bindinfo = (array) DB::fetch_first('SELECT * FROM %t WHERE uid=%d',array('jamesonread_appuser',$info['uid']));
			if(!$bindinfo){
				$data['qqopenid'] = null;
				$data['wxopenid'] = null;
			}else{
				$data['qqopenid'] = $bindinfo['qqopenid'];
				$data['wxopenid'] = $bindinfo['wxopenid'];
				$data['qqname']   = diconv($bindinfo['qqname'],CHARSET,'utf-8');
				$data['wxname']   = diconv($bindinfo['wxname'],CHARSET,'utf-8');
				$data['forumname'] = $data['name'];
				if($this->params['loginaction'] == 'bind'){
					unset($data['avatar']);
					$data['uid'] = $bindinfo[$logintype.'openid'];
				}
			}

			$this->_json(array(
				'status'=>1,
				'error'=>0,
				"data"=>$data
			));
		}else{
			// error
			$this->_json($this->_error('用户名或密码错误'));
		}
	}
	public function get_unbind()
	{
		$bbsuid = (int) $this->params['bbsuid'];
		$dqlogintype = trim($this->params['dqlogintype']);
		$openid = trim($this->params['openid']);
		if($old = DB::fetch_first('SELECT * FROM %t WHERE uid=%d AND '.DB::field($dqlogintype.'openid',$openid),array('jamesonread_appuser',$bbsuid))){
			$old[$dqlogintype.'openid'] = null;
			$old[$dqlogintype.'name'] = null;
			C::t('#jameson_read#jamesonread_appuser')->update($old['id'],$old);
			$this->_json(array(
				'status'=>1,
				'error'=>0
			));
		}else{
			$this->_json($this->_error('解绑失败=bbsuid-'.$bbsuid.' dqlogintype='.$dqlogintype.'  openid='.$openid));
		}
		# code...
	}
	// 使用qq或微信登录，判断是否已绑定本站帐号
	public function get_withotherlogin()
	{
		$type = $this->params['logintype'];
		$openid = $this->params['openid'];
		$bindinfo = (array) DB::fetch_first('SELECT * FROM %t WHERE '.DB::field($type.'openid',$openid),array('jamesonread_appuser'));
		if(!$bindinfo){
			$this->_json($this->_error('没有绑定'));
		}else{
			$data = $this->_getuserinfo($bindinfo['uid']);
			unset($data['avatar']);
			unset($data['uid']);
			$data['forumname'] = diconv(DB::result_first('SELECT username FROM %t WHERE uid=%d',array('common_member',$bindinfo['uid'])),CHARSET,'utf-8');
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				"data"=>$data
			));
		}
	}
	private function _getuserinfo($uid)
	{
		global $_G;
		loadcache('plugin');
		require '../source/plugin/jameson_read/function/function_jameson.php';
		init_jameson();
		$data = array();
		$data['trameid'] = $_G['jameson_read']['trameid'];
		$data['trametitle'] = diconv($_G['jameson_read']['trametitle'],CHARSET,'utf-8');
		$data['tramefield'] = $_G['jameson_read']['tramefield'];
		$data['tramenums'] = intval(DB::result_first("SELECT ".$data['tramefield']."  FROM %t WHERE uid=%d",array('common_member_count',$uid)));

		$data['dstrameid'] = $_G['jameson_read']['dstrameid'];
		$data['dstrametitle'] = diconv($_G['jameson_read']['dstrametitle'],CHARSET,'utf-8');
		$data['dstramefield'] = $_G['jameson_read']['dstramefield'];
		$data['dstramenums'] = intval(DB::result_first("SELECT ".$data['dstramefield']."  FROM %t WHERE uid=%d",array('common_member_count',$uid)));
		$data['avatar'] = avatar($uid,'middle',true);
		$data['uid'] = $uid;
		$data['group'] = $data['trametitle'].':'.$data['tramenums'];
		$data['bbsuid'] = $uid;
		$data['time'] = time();
		return $data;
	}
	// 添加到书架 
	public function get_addstore()
	{
		$uid = $this->params['bbsuid'];
		$book_id = $this->params['book_id'];
		$data = array();
		$data['book_id'] = $book_id;
		$data['uid'] = $uid;
		$data['status'] = 1;
		if(C::t('#jameson_read#jamesonread_favores')->hasFavore($data['uid'],$data['book_id'])){
			$this->_json(array(
				'status'=>1,
				'error'=>0
			));
		}else{
			if(C::t('#jameson_read#jamesonread_favores')->insertAndBooks($data)){
				$this->_json(array(
				'status'=>1,
				'error'=>0
				));
			}else{
				$this->_json($this->_error('出错请重试'));
			}
		}
	}
	public function get_delstore()
	{
		$uid = $this->params['bbsuid'];
		$bookids = explode('##',$this->params['book_id']);

		foreach ($bookids as $key => $value) {
			DB::delete('jamesonread_favores',"uid='".$uid."' AND book_id='".$value."'");
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0
		));
	}
	public function get_hyih()
	{
		$maxid = DB::result_first('SELECT book_id FROM %t WHERE status=%d ORDER BY book_id DESC',array('jamesonread_books',1));
		$minid = DB::result_first('SELECT book_id FROM %t WHERE status=%d ORDER BY book_id',array('jamesonread_books',1));
		$sjid = array(rand($minid,$maxid),rand($minid,$maxid),rand($minid,$maxid),rand($minid,$maxid),rand($minid,$maxid));
		$books = DB::fetch_all('SELECT * FROM %t WHERE '.DB::field('book_id',$sjid,'in'),array('jamesonread_books'));
		foreach ($books as $key => $value) {
			if($key < 3){
				$books[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			}else{
				unset($books[$key]);
			}
		}
		$this->_json(array(
			'status'=>1,
			'error'=>0,
			'books'=>$books
			));
	}
	// 书评广场
	public function get_spgc()
	{
		$shupingnums = $total = DB::result_first('SELECT count(*) FROM %t',array('jamesonread_dianping'));
		$total = ceil($total/$this->size);
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = $this->size*(intval($this->params['current'])-1);
		$sptype = strtolower($this->params['sptype']);
		$sptype = ($sptype=='new')?'addtime':"zhichi";
		$splist = DB::fetch_all('SELECT d.dp_id,d.book_id,d.text,d.uid,d.fandui,d.zhichi,d.addtime,b.book_name,b.image,b.author,b.desco FROM %t AS d INNER JOIN %t AS b ON d.book_id=b.book_id  ORDER BY d.'.$sptype.' DESC LIMIT %d,%d',array('jamesonread_dianping','jamesonread_books',$start,$this->size));
		foreach ($splist as $key => $value) {
			$splist[$key]['book_name'] = diconv($value['book_name'],CHARSET,'utf-8');
			$splist[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$splist[$key]['desco'] = cutstr(diconv($value['desco'],CHARSET,'utf-8'),66);
			$splist[$key]['text'] = diconv($value['text'],CHARSET,'utf-8');
			$splist[$key]['time'] = date('m/d',$value['addtime']);
		}
		if($splist){
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'total'=>$total,
				'data'=>$splist,
				'shupingnums'=>$shupingnums
			));
		}else{
			$this->_json($this->_error('没有了'));
		}
	}
	// 书评广场
	public function get_zzlist()
	{
		$zztype = strtolower($this->params['zztype']);
		if($zztype == 'new'){
			$total = DB::result_first('SELECT count(*) FROM %t',array('jamesonread_authors'));
		}else{
			$total = count(DB::fetch_all('SELECT uid FROM  %t  WHERE status=%d GROUP BY uid ',array('jamesonread_books',1)));
		}
		$total = ceil($total/$this->size);
		if($this->params['current']>$total){
			$this->_json($this->_error('没有了'));
		}
		$start = $this->size*(intval($this->params['current'])-1);
		if($zztype == 'new'){
			$data = DB::fetch_all('SELECT a.author_id,b.author,b.uid,count(b.book_id) AS nums FROM %t AS a INNER JOIN %t AS b ON a.author_id=b.uid WHERE b.status=%d ORDER BY a.author_id DESC LIMIT  %d,%d',array('jamesonread_authors','jamesonread_books',1,$start,$this->size));
		}else{
			$data = DB::fetch_all('SELECT author,uid,count(book_id) AS nums FROM %t WHERE status=%d GROUP BY uid ORDER BY nums DESC LIMIT  %d,%d',array('jamesonread_books',1,$start,$this->size));
		}
		
		
		foreach ($data as $key => $value) {
			$data[$key]['author'] = diconv($value['author'],CHARSET,'utf-8');
			$data[$key]['avatar'] = avatar($value['uid'],'middle',true);
			$data[$key]['fensinums'] = DB::result_first('SELECT count(*) FROM %t WHERE uid=%d',array('jamesonread_appguanzhu',$value['uid']));
			$data[$key]['dashangnums'] = DB::result_first('SELECT sum(price) FROM %t WHERE uid=%d',array('jamesonread_dashang',$value['uid']));
		}
		if($data){
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'total'=>$total,
				'data'=>$data
			));
		}else{
			$this->_json($this->_error('没有了'));
		}
	}
	function get_updateversion(){
		$version = trim($this->params['version']);
		$version = explode('.', $version);
		$appurl = trim(C::t('common_setting')->fetch('jamesonreadappurl'));
		$isnew = 0;
		if($appurl && preg_match("/\.apk$/i",$appurl)){
			$newversionstring = $newversion = substr($appurl, strrpos($appurl,'/')+1,-4);
			$newversion = explode('.',$newversion);
			if($newversion[0] > $version[0]){
				$isnew = 1;
			}else if($newversion[0]== $version[0]){
				if($newversion[1]>$version[1]){
					$isnew = 1;
				}else if($newversion[1]==$version[1]){
					if($newversion[2] > $version[2]){
						$isnew = 1;
					}
				}
			}
		}
		if($isnew){
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'version'=>$newversionstring,
				'newurl'=>$appurl
			));
		}else{
			$this->_json($this->_error('没有新版本'));
		}
	}
	public function get_reg()
	{
		global $_G;
		$username = ($this->params['username']);
		$username = daddslashes($username);
		$password = daddslashes(($this->params['pass']));
		$email = 'app_'.strtolower(random(6).random(6)).'@163.com';
		require_once './../uc_client/client.php';
		require_once './../source/class/class_member.php';
		require_once './../source/function/function_member.php';
		$uid = uc_user_register($username, $password, $email, '', '', $_G['clientip']);
		if($uid && ($uid>0)){
			$setregip = DB::result_first('SELECT count(*) FROM %t WHERE '.DB::field('ip',$_G['clientip']),array('common_regip'));
			if($setregip == 1) {
				C::t('common_regip')->update_count_by_ip($_G['clientip']);
			} else {
				C::t('common_regip')->insert(array('ip' => $_G['clientip'], 'count' => 1, 'dateline' => $_G['timestamp']));
			}
			$init_arr = array('credits' => explode(',', '1000,300,300,300,0,0,0,0,0'), 'profile'=>array(), 'emailstatus' =>0);
			C::t('common_member')->insert($uid, $username, md5($password), $email, $_G['clientip'], 10, $init_arr);
			$data = $this->_getuserinfo($uid);
			$data['name'] = $data['forumname'] = diconv($username,CHARSET,'utf-8');
			$data['dqlogintype'] = 'forum';
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'data'=>$data
			));
		}else{
			$msg = '注册失败';
			switch ($uid) {
				case "-1":
					$msg = '用户名不符合规则';
					break;
				case "-2":
					$msg = '此用户名禁止注册';
					break;
				case "-3":
					$msg = '已有相同用户名';
					break;
			}
			$this->_json($this->_error($msg));
		}
	}
	// 快速注册后绑定返回
	private function _bind($uid,$dqlogintype,$openid,$nickname)
	{
		$binddata = array();
		$binddata['uid'] = $uid;
		$binddata[$dqlogintype.'openid'] = $openid;
		$binddata[$dqlogintype.'name']= addslashes(diconv($nickname,'utf-8',CHARSET));
		C::t('#jameson_read#jamesonread_appuser')->insert($binddata);
	}
	public function get_fastreg($dqlogintype='',$openid='',$nickname='')
	{
		global $_G;
		$username = 'app_'.strtolower(random(5));
		$password = '123456';
		$email = 'app_'.strtolower(random(6).random(6)).'@163.com';
		$dqlogintype = $dqlogintype?$dqlogintype:$this->params['dqlogintype'];
		$openid = $openid?$openid:$this->params['openid'];
		$nickname = $nickname?$nickname:$this->params['nickname'];
		require_once './../uc_client/client.php';
		require_once './../source/class/class_member.php';
		require_once './../source/function/function_member.php';
		$uid = uc_user_register($username, $password, $email, '', '', $_G['clientip']);
		if($uid && ($uid>0)){
			$setregip = DB::result_first('SELECT count(*) FROM %t WHERE '.DB::field('ip',$_G['clientip']),array('common_regip'));
			if($setregip == 1) {
				C::t('common_regip')->update_count_by_ip($_G['clientip']);
			} else {
				C::t('common_regip')->insert(array('ip' => $_G['clientip'], 'count' => 1, 'dateline' => $_G['timestamp']));
			}
			$init_arr = array('credits' => explode(',', '1000,300,300,300,0,0,0,0,0'), 'profile'=>array(), 'emailstatus' =>0);
			C::t('common_member')->insert($uid, $username, md5($password), $email, $_G['clientip'], 10, $init_arr);
			// 进行绑定uid
			$this->_bind($uid,$dqlogintype,$openid,$nickname);
			$other = ($dqlogintype=='qq')?'wx':'qq';
			$data = $this->_getuserinfo($uid);
			$data[$other.'openid'] = $data[$other.'name'] = null;
			$data[$dqlogintype.'openid'] = $openid;
			$data[$dqlogintype.'name'] = $data['name'] = $nickname;
			$data['forumname'] = $username;
			$data['uid'] = $openid;
			$data['dqlogintype'] = $dqlogintype;
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				"data"=>$data,
				'user'=>$username,
				'pass'=>$password
			));
		}elseif(($uid == -3) && ($this->errnums < 2)){
			$this->get_fastreg($dqlogintype,$openid,$nickname);
			$this->errnums++;
		}else{
			$this->_json($this->_error('一键注册失败'));
		}
	}
	public function get_about()
	{
			$content = DB::result_first('SELECT adv FROM %t WHERE type=%d',array('jamesonread_topics',88));
			$content = diconv($content,CHARSET,'utf-8');
			$this->_json(array(
				'status'=>1,
				'error'=>0,
				'content'=>$content
			));
	}
	public function get_postshuping()
	{
		$data = array();
		$data['book_id'] = intval($this->params['book_id']);
		$text = daddslashes(diconv(($this->params['text']),'utf-8',CHARSET));
		$data['text'] = cutstr($text,300);
		$data['uid'] = intval($this->params['bbsuid']);
		$data['addtime'] = time();
		if(C::t('#jameson_read#jamesonread_dianping')->insert($data,true)){
			$this->_json(array(
				'status'=>1,
				'error'=>0
			));
		}else{
			$this->_json($this->_error('发布书评失败'));
		}
	}
	private function _error($msg){
		return array(
			'status'=>0,
			'error'=>$msg
		);
	}
	private function _json($data)
	{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
		exit;
	}
	private function _isutf8($str){
		$len = strlen($str);
		for($i = 0; $i < $len; $i++){
			$c = ord($str[$i]);
			if ($c > 128) {
			if (($c > 247)) return false;
			elseif ($c > 239) $bytes = 4;
			elseif ($c > 223) $bytes = 3;
			elseif ($c > 191) $bytes = 2;
			else return false;
			if (($i + $bytes) > $len) return false;
			while ($bytes > 1) {
			$i++;
			$b = ord($str[$i]);
			if ($b < 128 || $b > 191) return false;
			$bytes--;
			}
			}
		}
		return true;
	}
}