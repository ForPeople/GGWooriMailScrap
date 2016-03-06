<?php
if(!defined("__ZBXE__") && !defined("__XE__")) exit();

/**
 * @file ggwoorimailscrap.addon.php
 * @author 포피플 (xeadmin@forppl.com)
 * @brief 우리메일 연동 문서페이지/위젯페이지(직접입력)/게시판(게시글)을 스크랩하여 이메일로 전송하는 애드온
**/

if(Context::get('act') == 'dispBoardWrite') return;
if(!Context::get('logged_info')) return;

$module_info = Context::get('module_info');
$oDocumentModel = getModel('document');

if(($module_info->module == 'board' || $module_info->module == 'page')) {
	if(!$addon_info->text_align) $addon_info->text_align = 'left';

	Context::set('module_info',$module_info);
	
	Context::set('addon_info',$addon_info);
	$logged_info = Context::get('logged_info');
	Context::set('logged_info',$logged_info);
	$addon_info->gg_skin = $addon_info->gg_skin ? $addon_info->gg_skin : 'default';
	$oTemplate = new TemplateHandler();
	$template_btn_text = $oTemplate->compile('./addons/ggwoorimailscrap/skins/'.$addon_info->gg_skin, 'index');
	$btn_text = '<div style=text-align:'.$addon_info->text_align.';width:100%;height:50px;z-index:99999;>'.$template_btn_text.'</div>';
}

if($called_position == 'after_module_proc' && $module_info->module == 'board' && Context::get('document_srl')) {
	$oDocument = $oDocumentModel->getDocument(Context::get('document_srl'));
	$ggoutput = new stdClass();

	if($addon_info->text_position == 'top') $ggoutput = $btn_text.$oDocument->variables['content'];

	else $ggoutput = $oDocument->variables['content'].$btn_text;

	$oDocument->variables['content'] = $ggoutput;

} elseif($called_position == 'before_display_content' && $module_info->module == 'page') {
	$oDocument = $oDocumentModel->getDocumentList($module_info);
	$oDocument = $oDocument->data[1];
	
	if($addon_info->text_position == 'top') $output = str_replace($oDocument->variables['content'], $btn_text.$oDocument->variables['content'], $output);

	else $output = str_replace($oDocument->variables['content'], $oDocument->variables['content'].$btn_text, $output);
}

if($called_position == 'before_display_content' && Context::get('ggtype') == 'ggwoorimailscrap') {
	$config = new stdClass();
	$config->w_serv_url = 'woorimail.com'; 
	$config->w_ssl = $addon_info->w_ssl; 
	$config->w_ssl_port = '20080'; 
	$config->w_authkey = $addon_info->w_authkey; 
	$config->w_domain = $addon_info->w_domain;

	$ggDocument = $oDocumentModel->getDocument(Context::get('document_srl'));
	$title = $ggDocument->variables['title'];
	$content = $ggDocument->variables['content'];

	$config->w_title = $title ? $title : '제목이 없습니다.'; 
	$config->w_title = '[스크랩] '.$config->w_title;
	$config->w_content = $content ? $content : '내용이 없습니다.'; 

	$config->w_receiver_nickname = $logged_info->nick_name; 
	$config->w_receiver_email = $logged_info->email_address; 

	$count_member = explode(',',$config->w_receiver_nickname);
	
	for($i=0;$i<count($count_member);$i++)
	{
		$config->w_member_regdate .= date('YmdHis') . ',';
	}

		$config->w_member_regdate = substr($config->w_member_regdate,0,-1); 
		 
		$config->w_sender_email = $addon_info->w_sender_email; 
		$config->w_sender_nickname = $addon_info->w_sender_nickname;
		  
		$config->w_wms_domain = $addon_info->w_wms_domain ? $addon_info->w_wms_domain : 'woorimail.com'; 
		$config->w_wms_nick = $addon_info->w_wms_nick ? $addon_info->w_wms_nick : 'webmaster'; 
		 
		$config->w_type = 'api'; 
		 
		$config->w_mid = 'auth_woorimail';
		$config->w_act = 'dispWwapimanagerMailApi';
		 
		//$config->w_callback = getFullUrl('','mid',Context::get('mid'),'document_srl',Context::get('document_srl'),'status','ok') . '&'; 
		//$config->w_callback = getFullUrl('','mid',Context::get('mid'),'document_srl',Context::get('document_srl')) . '&'; 
		$config->w_callback = '';
		 
		$w_serv_url = $config->w_serv_url;
	
	if($config->w_ssl == 'N' || !$config->w_ssl)
	{
		$w_ssl = 'http://'; 
		$w_ssl_port = ''; 
	}
	elseif($config->w_ssl == 'Y')
	{
		$w_ssl = 'https://'; 
		$w_ssl_port = ':' . $config->w_ssl_port; 
	}

	$url = $w_ssl . $w_serv_url . $w_ssl_port . '/index.php';	
	$post_data = array(
		'act' => $config->w_act,
		'authkey' => $config->w_authkey,
		'mid' => $config->w_mid,
		'domain' => $config->w_domain,
		'type' => $config->w_type,
		'title' => $config->w_title,
		'content' => $config->w_content,
		'sender_nickname' => $config->w_sender_nickname,
		'sender_email' => $config->w_sender_email,
		'receiver_nickname' => $config->w_receiver_nickname,
		'receiver_email' => $config->w_receiver_email,
		'member_regdate' => $config->w_member_regdate,
		'wms_domain' => $config->w_wms_domain,
		'wms_nick' => $config->w_wms_nick,
		'callback' => $config->w_callback
	);
	 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	 
	if($config->w_ssl == 'Y') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	}
	 
	$response = curl_exec($ch);
	curl_close($ch);
	//debugPrint('');

	$ggjson = json_decode($response);
	$returnUrl = getNotEncodedUrl('', 'mid', Context::get('mid'), 'document_srl', Context::get('document_srl'));
	if($ggjson->result == 'OK') {
		//$this->setRedirectUrl($returnUrl);
		//header("Location:" . $returnUrl);
		echo '<script>alert("이메일 전송이 완료되었습니다.");location.href="'.$returnUrl.'";</script>';
	} else {
		echo '<script>alert("Error Message : '.$ggjson->error_msg.'");location.href="'.$returnUrl.'";</script>';
	}

}
?>