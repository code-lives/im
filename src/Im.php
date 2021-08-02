<?php

namespace Im\api;

class Im
{
	private $key = false;
	private $sdkappid = 0;
	private $random;
	private $Imurl = 'https://console.tim.qq.com/';
	private $admin_id;
	private $parm_url = [
		'ACCOUNT_IMPORT_URL' => "v4/im_open_login_svc/account_import", //单个账号倒入
		'ACCOUNT_CHECK_URL' => 'v4/im_open_login_svc/account_check', //检测账号是否导入IM
		'SENDMSG_URL' => 'v4/openim/sendmsg', //单发消息
		'GROUP_URL' => 'v4/group_open_http_svc/get_group_member_info', //获取群成员信息
		'ADD_GROUP' => 'v4/group_open_http_svc/add_group_member', //加入群组
		'CREATE_GROUP' => 'v4/group_open_http_svc/create_group', //创建群组
		'DELETE_GROUP' => 'v4/group_open_http_svc/delete_group_member', //删除群组成员
		'MSG_READ' => 'v4/openim/admin_set_msg_read', //设置用户的某个单聊会话的消息全部已读
		'GET_HISTORY' => 'v4/open_msg_svc/get_history', //拉取聊天获取
		'SET_INFO' => 'v4/profile/portrait_set', //设置资料
	];

	public function __construct($sdkappid, $key, $admin_id = null)
	{
		$this->sdkappid = $sdkappid;
		$this->key = $key;
		$this->random = time();
		$this->admin_id = $admin_id;
	}
	/**
	 * 【功能说明】进行IM 生成url
	 * @param string admin_id 管理人员账号
	 * @param string path 接口路径
	 */
	private function set_url($path)
	{
		if (!$this->admin_id) {
			throw new \Exception('error admin_id');
		}
		$sign = $this->genUserSig($this->admin_id);

		return $this->Imurl . $this->parm_url[$path] . "?sdkappid=" . $this->sdkappid . "&identifier=" . $this->admin_id . "&usersig=" . $sign . "&random=" . $this->random . "&contenttype=json";
	}
	/**
	 * 【功能说明】进行IM 账号导入IM的聊天池，后续就不需要绑定
	 *【参数说明】
	 * @param string uid  需要绑定的用户uid
	 * @param string nickname  需要绑定的用户的名称
	 * @param string images  需要绑定的用户的头像
	 * @return bool true false
	 */
	public function account_import(string $uid, string $nickname, string $images)
	{
		$url = $this->set_url('ACCOUNT_IMPORT_URL');
		$data = array(
			'Identifier' => $uid,
			'Nick' => $nickname,
			'FaceUrl' => $images
		);
		$data = $this->curl_get($url, json_encode($data));
		if (!empty($data) && $data['ActionStatus'] == "OK") {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * 【功能说明】个人资料设置
	 *【参数说明】
	 * @param string uid  需要绑定的用户uid
	 * @param array data  需要配置的参数 key=val
	 * @return bool true false
	 */
	public function set_info(string $uid, $profileitem)
	{
		$url = $this->set_url('SET_INFO');
		$info = array(
			'From_Account' => $uid,
			'ProfileItem' => $profileitem
		);
		$data = $this->curl_get($url, json_encode($info));
		if (!empty($data) && $data['ActionStatus'] == "OK") {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * 【功能说明】 检测账号是否绑定过
	 *  @param string  uid  检测的id
	 */
	public function check_bind(string $uid)
	{
		$url = $this->set_url('ACCOUNT_CHECK_URL');
		$data['CheckItem'][] = array('UserID' => $uid);
		$data = $this->curl_get($url, json_encode($data));
		if (!empty($data) && $data['ActionStatus'] == 'OK' && $data['ResultItem'][0]['AccountStatus'] == "Imported") {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * 【功能说明】发送自定义消息
	 * @param string from_id 发送人id
	 * @param string to_id 接收人id //接收人 检测
	 * @param string data 发送的内容
	 */
	public function from_to(string $from_id, string $to_id, $content)
	{
		$url = $this->set_url('SENDMSG_URL');
		$datas = array(
			'SyncOtherMachine' => 1,
			'From_Account' => $from_id,
			'To_Account' => $to_id,
			'MsgRandom' => time(),
			'MsgTimeStamp' => time(),
			'MsgBody' => array(
				array(
					'MsgType' => 'TIMCustomElem',
					'MsgContent' => array(
						'Ext' => json_encode($content),
					),
				)
			),
		);
		$data = $this->curl_get($url, json_encode($datas));
		if (!empty($data) && $data['ActionStatus'] == "OK") {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 【功能说明】查询群成员
	 * @param string work_name 群名称
	 * @param string offset 从第几个成员开始获取，如果不填则默认为0，表示从第一个成员开始获取
	 * @param string limit  一次最多获取多少个成员的资料，不得超过6000。如果不填，则获取群内全部成员的信息
	 */
	public function get_work_list(string $work_name, int $offset = 0, int $limit = 20)
	{
		$url = $this->set_url('GROUP_URL');
		$data = array(
			'GroupId' => $work_name,
			'Offset' => $offset,
			'Limit' => $limit,
		);
		$data = $this->curl_get($url, json_encode($data));
		// dd($data);
		if (!empty($data) && $data['ActionStatus'] == "OK") {
			return $data;
		} else {
			return false;
		}
	}
	/**
	 * 【功能说明】加入群聊
	 * @param string work_name 群名称
	 * @param string offset 从第几个成员开始获取，如果不填则默认为0，表示从第一个成员开始获取
	 * @param string limit  一次最多获取多少个成员的资料，不得超过6000。如果不填，则获取群内全部成员的信息
	 * @return bool ture成功 false失败
	 */
	public function add_work(string $work_name, string $uid)
	{
		$url = $this->set_url('ADD_GROUP');
		$data = array(
			'GroupId' => $work_name,
			'Silence' => 1,
			'MemberList' => array(
				array(
					'Member_Account' => $uid,
				)
			),
		);
		$data = $this->curl_get($url, json_encode($data));
		if (!empty($data) && $data['ActionStatus'] == "OK" && $data['ErrorCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * 【功能说明】删除群成员
	 * @param string work_name 群名称
	 * @param string uid 群成员id
	 * @return bool ture成功 false失败
	 */
	public function delete_work_user(string $work_name, $uid)
	{
		$url = $this->set_url('DELETE_GROUP');
		$data = array(
			'GroupId' => $work_name,
			'Silence' => 1,
			'MemberToDel_Account' => [$uid]
		);
		$data = $this->curl_get($url, json_encode($data));
		if (!empty($data) && $data['ActionStatus'] == "OK" && $data['ErrorCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * 【功能说明】设置用户的某个单聊会话的消息全部已读
	 * @param $from_id 操作的用户
	 * @param $to_id 被读用户
	 * @return bool ture成功 false失败
	 */
	public function msg_read($from_id, $to_id)
	{
		$url = $this->set_url('MSG_READ');
		$data = array(
			'Report_Account' => $from_id,
			'Peer_Account' => $to_id,
		);
		$data = $this->curl_get($url, json_encode($data));
		if (!empty($data) && $data['ActionStatus'] == "OK" && $data['ErrorCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * 【功能说明】拉取聊天记录压缩包
	 * @return URL 压缩包下载地址
	 * @return ExpireTime 过期时间
	 * @return FileSize 文件大小 GZip 压缩前的文件大小（单位 Byte）
	 * @return FileMD5 GZip压缩前的文件 MD5
	 * @return GzipSize GZip压缩后的文件大小（单位 Byte）
	 * @return GzipMD5 GZip 压缩后的文件大小（单位 Byte）
	 *
	 */
	public function get_history($parm)
	{
		$url = $this->set_url('GET_HISTORY');
		$data = array(
			'ChatType' => $parm['ChatType'],
			'MsgTime' => $parm['MsgTime'],
		);
		$data = $this->curl_get($url, json_encode($data));
		if (!empty($data) && $data['ActionStatus'] == "OK" && $data['ErrorCode'] == 0) {
			return $data['File'];
		} else {
			return false;
		}
	}

	/**
	 * 【功能说明】创建群组
	 * @param string work_name 群名称
	 * @param string offset 从第几个成员开始获取，如果不填则默认为0，表示从第一个成员开始获取
	 * @param string limit  一次最多获取多少个成员的资料，不得超过6000。如果不填，则获取群内全部成员的信息
	 * @return string  群id 或者false
	 */
	public function create_work(string $create_id, $group_name, $private = 'Public', $ApplyJoinOption = 'FreeAccess')
	{
		$url = $this->set_url('CREATE_GROUP');
		$data = array(
			'Owner_Account' => $create_id,
			'Type' => $private,
			'Name' => $group_name,
			'ApplyJoinOption' => $ApplyJoinOption,
		);

		$data = $this->curl_get($url, json_encode($data));

		if (!empty($data) && $data['ActionStatus'] == "OK" && $data['ErrorCode'] == 0) {
			return $data['GroupId'];
		} else {
			return false;
		}
	}
	/**
	 *【功能说明】用于签发 TRTC 和 IM 服务中必须要使用的 UserSig 鉴权票据
	 *
	 *【参数说明】
	 * @param string userid - 用户id，限制长度为32字节，只允许包含大小写英文字母（a-zA-Z）、数字（0-9）及下划线和连词符。
	 * @param string expire - UserSig 票据的过期时间，单位是秒，比如 86400 代表生成的 UserSig 票据在一天后就无法再使用了。
	 * @return string 签名字符串
	 * @throws \Exception
	 */

	public function genUserSig($userid, $expire = 86400 * 180)
	{
		return $this->__genSig($userid, $expire, '', false);
	}

	/**
	 *【功能说明】
	 * 用于签发 TRTC 进房参数中可选的 PrivateMapKey 权限票据。
	 * PrivateMapKey 需要跟 UserSig 一起使用，但 PrivateMapKey 比 UserSig 有更强的权限控制能力：
	 *  - UserSig 只能控制某个 UserID 有无使用 TRTC 服务的权限，只要 UserSig 正确，其对应的 UserID 可以进出任意房间。
	 *  - PrivateMapKey 则是将 UserID 的权限控制的更加严格，包括能不能进入某个房间，能不能在该房间里上行音视频等等。
	 * 如果要开启 PrivateMapKey 严格权限位校验，需要在【实时音视频控制台】=>【应用管理】=>【应用信息】中打开“启动权限密钥”开关。
	 *
	 *【参数说明】
	 * @param userid - 用户id，限制长度为32字节，只允许包含大小写英文字母（a-zA-Z）、数字（0-9）及下划线和连词符。
	 * @param expire - PrivateMapKey 票据的过期时间，单位是秒，比如 86400 生成的 PrivateMapKey 票据在一天后就无法再使用了。
	 * @param roomid - 房间号，用于指定该 userid 可以进入的房间号
	 * @param privilegeMap - 权限位，使用了一个字节中的 8 个比特位，分别代表八个具体的功能权限开关：
	 *  - 第 1 位：0000 0001 = 1，创建房间的权限
	 *  - 第 2 位：0000 0010 = 2，加入房间的权限
	 *  - 第 3 位：0000 0100 = 4，发送语音的权限
	 *  - 第 4 位：0000 1000 = 8，接收语音的权限
	 *  - 第 5 位：0001 0000 = 16，发送视频的权限
	 *  - 第 6 位：0010 0000 = 32，接收视频的权限
	 *  - 第 7 位：0100 0000 = 64，发送辅路（也就是屏幕分享）视频的权限
	 *  - 第 8 位：1000 0000 = 200，接收辅路（也就是屏幕分享）视频的权限
	 *  - privilegeMap == 1111 1111 == 255 代表该 userid 在该 roomid 房间内的所有功能权限。
	 *  - privilegeMap == 0010 1010 == 42  代表该 userid 拥有加入房间和接收音视频数据的权限，但不具备其他权限。
	 */

	public function genPrivateMapKey($userid, $expire, $roomid, $privilegeMap)
	{
		$userbuf = $this->__genUserBuf($userid, $roomid, $expire, $privilegeMap, 0, '');
		return $this->__genSig($userid, $expire, $userbuf, true);
	}
	/**
	 *【功能说明】
	 * 用于签发 TRTC 进房参数中可选的 PrivateMapKey 权限票据。
	 * PrivateMapKey 需要跟 UserSig 一起使用，但 PrivateMapKey 比 UserSig 有更强的权限控制能力：
	 *  - UserSig 只能控制某个 UserID 有无使用 TRTC 服务的权限，只要 UserSig 正确，其对应的 UserID 可以进出任意房间。
	 *  - PrivateMapKey 则是将 UserID 的权限控制的更加严格，包括能不能进入某个房间，能不能在该房间里上行音视频等等。
	 * 如果要开启 PrivateMapKey 严格权限位校验，需要在【实时音视频控制台】=>【应用管理】=>【应用信息】中打开“启动权限密钥”开关。
	 *
	 *【参数说明】
	 * @param userid - 用户id，限制长度为32字节，只允许包含大小写英文字母（a-zA-Z）、数字（0-9）及下划线和连词符。
	 * @param expire - PrivateMapKey 票据的过期时间，单位是秒，比如 86400 生成的 PrivateMapKey 票据在一天后就无法再使用了。
	 * @param roomstr - 房间号，用于指定该 userid 可以进入的房间号
	 * @param privilegeMap - 权限位，使用了一个字节中的 8 个比特位，分别代表八个具体的功能权限开关：
	 *  - 第 1 位：0000 0001 = 1，创建房间的权限
	 *  - 第 2 位：0000 0010 = 2，加入房间的权限
	 *  - 第 3 位：0000 0100 = 4，发送语音的权限
	 *  - 第 4 位：0000 1000 = 8，接收语音的权限
	 *  - 第 5 位：0001 0000 = 16，发送视频的权限
	 *  - 第 6 位：0010 0000 = 32，接收视频的权限
	 *  - 第 7 位：0100 0000 = 64，发送辅路（也就是屏幕分享）视频的权限
	 *  - 第 8 位：1000 0000 = 200，接收辅路（也就是屏幕分享）视频的权限
	 *  - privilegeMap == 1111 1111 == 255 代表该 userid 在该 roomid 房间内的所有功能权限。
	 *  - privilegeMap == 0010 1010 == 42  代表该 userid 拥有加入房间和接收音视频数据的权限，但不具备其他权限。
	 */

	public function genPrivateMapKeyWithStringRoomID($userid, $expire, $roomstr, $privilegeMap)
	{
		$userbuf = $this->__genUserBuf($userid, 0, $expire, $privilegeMap, 0, $roomstr);
		return $this->__genSig($userid, $expire, $userbuf, true);
	}



	/**
	 * 用于 url 的 base64 encode
	 * '+' => '*', '/' => '-', '=' => '_'
	 * @param string $string 需要编码的数据
	 * @return string 编码后的base64串，失败返回false
	 * @throws \Exception
	 */

	private function base64_url_encode($string)
	{
		static $replace = array('+' => '*', '/' => '-', '=' => '_');
		$base64 = base64_encode($string);
		if ($base64 === false) {
			throw new \Exception('base64_encode error');
		}
		return str_replace(array_keys($replace), array_values($replace), $base64);
	}

	/**
	 * 用于 url 的 base64 decode
	 * '+' => '*', '/' => '-', '=' => '_'
	 * @param string $base64 需要解码的base64串
	 * @return string 解码后的数据，失败返回false
	 * @throws \Exception
	 */

	private function base64_url_decode($base64)
	{
		static $replace = array('+' => '*', '/' => '-', '=' => '_');
		$string = str_replace(array_values($replace), array_keys($replace), $base64);
		$result = base64_decode($string);
		if ($result == false) {
			throw new \Exception('base64_url_decode error');
		}
		return $result;
	}
	/**
	 * TRTC业务进房权限加密串使用用户定义的userbuf
	 * @brief 生成 userbuf
	 * @param account 用户名
	 * @param dwSdkappid sdkappid
	 * @param dwAuthID  数字房间号
	 * @param dwExpTime 过期时间：该权限加密串的过期时间. 过期时间 = now+dwExpTime
	 * @param dwPrivilegeMap 用户权限，255表示所有权限
	 * @param dwAccountType 用户类型, 默认为0
	 * @param roomStr 字符串房间号
	 * @return userbuf string  返回的userbuf
	 */

	private function __genUserBuf($account, $dwAuthID, $dwExpTime, $dwPrivilegeMap, $dwAccountType, $roomStr)
	{

		//cVer  unsigned char/1 版本号，填0
		if ($roomStr == '')
			$userbuf = pack('C1', '0');
		else
			$userbuf = pack('C1', '1');

		$userbuf .= pack('n', strlen($account));
		//wAccountLen   unsigned short /2   第三方自己的帐号长度
		$userbuf .= pack('a' . strlen($account), $account);
		//buffAccount   wAccountLen 第三方自己的帐号字符
		$userbuf .= pack('N', $this->sdkappid);
		//dwSdkAppid    unsigned int/4  sdkappid
		$userbuf .= pack('N', $dwAuthID);
		//dwAuthId  unsigned int/4  群组号码/音视频房间号
		$expire = $dwExpTime + time();
		$userbuf .= pack('N', $expire);
		//dwExpTime unsigned int/4  过期时间 （当前时间 + 有效期（单位：秒，建议300秒））
		$userbuf .= pack('N', $dwPrivilegeMap);
		//dwPrivilegeMap unsigned int/4  权限位
		$userbuf .= pack('N', $dwAccountType);
		//dwAccountType  unsigned int/4
		if ($roomStr != '') {
			$userbuf .= pack('n', strlen($roomStr));
			//roomStrLen   unsigned short /2   字符串房间号长度
			$userbuf .= pack('a' . strlen($roomStr), $roomStr);
			//roomStr   roomStrLen 字符串房间号
		}
		return $userbuf;
	}
	/**
	 * 使用 hmac sha256 生成 sig 字段内容，经过 base64 编码
	 * @param $identifier 用户名，utf-8 编码
	 * @param $curr_time 当前生成 sig 的 unix 时间戳
	 * @param $expire 有效期，单位秒
	 * @param $base64_userbuf base64 编码后的 userbuf
	 * @param $userbuf_enabled 是否开启 userbuf
	 * @return string base64 后的 sig
	 */

	private function hmacsha256($identifier, $curr_time, $expire, $base64_userbuf, $userbuf_enabled)
	{
		$content_to_be_signed = 'TLS.identifier:' . $identifier . "\n"
			. 'TLS.sdkappid:' . $this->sdkappid . "\n"
			. 'TLS.time:' . $curr_time . "\n"
			. 'TLS.expire:' . $expire . "\n";
		if (true == $userbuf_enabled) {
			$content_to_be_signed .= 'TLS.userbuf:' . $base64_userbuf . "\n";
		}
		return base64_encode(hash_hmac('sha256', $content_to_be_signed, $this->key, true));
	}

	/**
	 * 生成签名。
	 *
	 * @param $identifier 用户账号
	 * @param int $expire 过期时间，单位秒，默认 180 天
	 * @param $userbuf base64 编码后的 userbuf
	 * @param $userbuf_enabled 是否开启 userbuf
	 * @return string 签名字符串
	 * @throws \Exception
	 */

	private function __genSig($identifier, $expire, $userbuf, $userbuf_enabled)
	{
		$curr_time = time();
		$sig_array = array(
			'TLS.ver' => '2.0',
			'TLS.identifier' => strval($identifier),
			'TLS.sdkappid' => intval($this->sdkappid),
			'TLS.expire' => intval($expire),
			'TLS.time' => intval($curr_time)
		);

		$base64_userbuf = '';
		if (true == $userbuf_enabled) {
			$base64_userbuf = base64_encode($userbuf);
			$sig_array['TLS.userbuf'] = strval($base64_userbuf);
		}

		$sig_array['TLS.sig'] = $this->hmacsha256($identifier, $curr_time, $expire, $base64_userbuf, $userbuf_enabled);
		if ($sig_array['TLS.sig'] === false) {
			throw new \Exception('base64_encode error');
		}
		$json_str_sig = json_encode($sig_array);
		if ($json_str_sig === false) {
			throw new \Exception('json_encode error');
		}
		$compressed = gzcompress($json_str_sig);
		if ($compressed === false) {
			throw new \Exception('gzcompress error');
		}
		return $this->base64_url_encode($compressed);
	}

	/**
	 * 验证签名。
	 *
	 * @param string $sig 签名内容
	 * @param string $identifier 需要验证用户名，utf-8 编码
	 * @param int $init_time 返回的生成时间，unix 时间戳
	 * @param int $expire_time 返回的有效期，单位秒
	 * @param string $userbuf 返回的用户数据
	 * @param string $error_msg 失败时的错误信息
	 * @return boolean 验证是否成功
	 * @throws \Exception
	 */

	private function __verifySig($sig, $identifier, &$init_time, &$expire_time, &$userbuf, &$error_msg)
	{
		try {
			$error_msg = '';
			$compressed_sig = $this->base64_url_decode($sig);
			$pre_level = error_reporting(E_ERROR);
			$uncompressed_sig = gzuncompress($compressed_sig);
			error_reporting($pre_level);
			if ($uncompressed_sig === false) {
				throw new \Exception('gzuncompress error');
			}
			$sig_doc = json_decode($uncompressed_sig);
			if ($sig_doc == false) {
				throw new \Exception('json_decode error');
			}
			$sig_doc = (array)$sig_doc;
			if ($sig_doc['TLS.identifier'] !== $identifier) {
				throw new \Exception("identifier dosen't match");
			}
			if ($sig_doc['TLS.sdkappid'] != $this->sdkappid) {
				throw new \Exception("sdkappid dosen't match");
			}
			$sig = $sig_doc['TLS.sig'];
			if ($sig == false) {
				throw new \Exception('sig field is missing');
			}

			$init_time = $sig_doc['TLS.time'];
			$expire_time = $sig_doc['TLS.expire'];

			$curr_time = time();
			if ($curr_time > $init_time + $expire_time) {
				throw new \Exception('sig expired');
			}

			$userbuf_enabled = false;
			$base64_userbuf = '';
			if (isset($sig_doc['TLS.userbuf'])) {
				$base64_userbuf = $sig_doc['TLS.userbuf'];
				$userbuf = base64_decode($base64_userbuf);
				$userbuf_enabled = true;
			}
			$sigCalculated = $this->hmacsha256($identifier, $init_time, $expire_time, $base64_userbuf, $userbuf_enabled);

			if ($sig != $sigCalculated) {
				throw new \Exception('verify failed');
			}

			return true;
		} catch (\Exception $ex) {
			$error_msg = $ex->getMessage();
			return false;
		}
	}

	/**
	 * 带 userbuf 验证签名。
	 *
	 * @param string $sig 签名内容
	 * @param string $identifier 需要验证用户名，utf-8 编码
	 * @param int $init_time 返回的生成时间，unix 时间戳
	 * @param int $expire_time 返回的有效期，单位秒
	 * @param string $error_msg 失败时的错误信息
	 * @return boolean 验证是否成功
	 * @throws \Exception
	 */

	public function verifySig($sig, $identifier, &$init_time, &$expire_time, &$error_msg)
	{
		$userbuf = '';
		return $this->__verifySig($sig, $identifier, $init_time, $expire_time, $userbuf, $error_msg);
	}

	/**
	 * 验证签名
	 * @param string $sig 签名内容
	 * @param string $identifier 需要验证用户名，utf-8 编码
	 * @param int $init_time 返回的生成时间，unix 时间戳
	 * @param int $expire_time 返回的有效期，单位秒
	 * @param string $userbuf 返回的用户数据
	 * @param string $error_msg 失败时的错误信息
	 * @return boolean 验证是否成功
	 * @throws \Exception
	 */

	public function verifySigWithUserBuf($sig, $identifier, &$init_time, &$expire_time, &$userbuf, &$error_msg)
	{
		return $this->__verifySig($sig, $identifier, $init_time, $expire_time, $userbuf, $error_msg);
	}
	private function curl_get($url, $array)
	{

		$curl = curl_init();
		//设置提交的url
		curl_setopt($curl, CURLOPT_URL, $url);
		//设置头文件的信息作为数据流输出
		curl_setopt($curl, CURLOPT_HEADER, 0);
		//设置获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		//设置post方式提交
		curl_setopt($curl, CURLOPT_POST, 1);
		//设置post数据
		$post_data = $array;
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		//执行命令
		$data = curl_exec($curl);
		//关闭URL请求
		curl_close($curl);
		return json_decode($data, true);
	}
}
