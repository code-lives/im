# Im

腾讯Im 加入聊天池 系统发送自定义消息 加入群组 创建群组 获取群成员列表 检测用户是否在加入Im 更改未读状态 拉取聊天记录 设置个人资料

## 设置参数
```php
im_id      Im 申请的id

im_key     Im 申请的key

admin_id   Im 管理员账号【选填】用于发送一些消息。比如 A和B在聊天  拿着A账号给B发送一条消息 或者 创建群组 查询群组等

```
## 生成 UserSig

```php

$Im = new Im\api\Im($im_id,$im_key);

$Im->genUserSig($uid);

```
## 把用户uid 导入到Im的聊天配置中【相当于加入该项目的群】  

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->account_import($uid,$nickname,$images); //用户的uid 姓名 头像

返回类型 true  false

```
## 设置个人资料

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->set_info($uid,$data); //用户的uid 用户字段配置

返回类型 true  false

```
## 检测用户是否在加入Im

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->check_bind($uid); //用户uid

返回类型 true  false

```

## 管理员发送自定义消息[new 的必须带 _admin_id]

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->from_to($from_id,$to_id,$content);  //发送人id 接收人id 内容自定义的数组或字符串

返回类型 true  false

```

## 设置用户的某个单聊会话的消息全部已读[new 的必须带 _admin_id]

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->msg_read($from_id,$to_id);  //发送人id 接收人id 内容自定义的数组或字符串

返回类型 true  false

```
## 查询群组里面有多少人

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->get_work_list($work_name,$offset,$limit);  //群名称  offset 类似于分页 limit 每页几条

返回类型 array()  false

```

## 加入群聊

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->add_work($work_name,$uid);  //群名称  用户uid

返回类型 true  false

```

## 创建群聊

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->create_work($create_id，$group_name);  //创建人id  群名称

返回类型 群id  false

```

## 消息已读

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->msg_read($from_id，$to_id);  //fromy_id 操作用户  to_id 被读用户

返回类型 群id  false

```

## 拉取聊天记录

```php

$Im = new Im\api\Im($im_id,$im_key,$admin_id);

$Im->get_history($parm);  //$parm['ChatType']消息类型 $parm['MsgTime'] 时间戳 2021010221=2021年10月1日21时-21时59分记录

返回类型 群id  false

```
