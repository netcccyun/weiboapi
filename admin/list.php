<?php
include("../includes/common.php");
$title='账号列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<style>
.table>tbody>tr>td {
	vertical-align: middle;
    max-width: 360px;
	word-break: break-all;
}
.img-circle{margin-right: 7px;}
</style>
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-11 col-lg-10 center-block" style="float: none;">
<?php

if($_GET['my']=='search') {
	$sql=" `uin`='{$_GET['kw']}'";
	$numrows=$DB->getColumn("SELECT count(*) from weiboapi_account WHERE{$sql}");
	$con='包含 '.$_GET['kw'].' 的共有 <b>'.$numrows.'</b> 个记录';
	$link='&my=search&kw='.$_GET['kw'];
}else{
	$numrows=$DB->getColumn("SELECT count(*) from weiboapi_account WHERE 1");
	$sql=" 1";
	$con='本站共有 <b>'.$numrows.'</b> 个账号';
}

echo '<form action="list.php" method="GET" class="form-inline"><input type="hidden" name="my" value="search">
<div class="form-group">
  <label>搜索</label>
  <input type="text" class="form-control" name="kw" placeholder="UID、用户名或昵称">
</div>
<button type="submit" class="btn btn-primary">搜索</button>&nbsp;<a href="./add.php" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> 添加账号</a>
</form>';
echo $con;
?>
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead><tr><th>ID</th><th>头像</th><th>UID</th><th>昵称</th><th>更新时间</th><th>上次使用</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM weiboapi_account WHERE{$sql} order by id desc limit $offset,$pagesize");
while($res = $rs->fetch())
{
  $avatar = './download.php?mod=faceimg&uid='.$res['uid'];
echo '<tr><td><b>'.$res['id'].'</b></td><td><img src="'.$avatar.'" alt="Avatar" width="40" class="img-circle"></td><td><a href="https://weibo.com/u/'.$res['uid'].'" target="_blank" rel="noreferrer">'.$res['uid'].'</a></td><td>'.$res['nickname'].'</td><td>'.$res['refreshtime'].'</td><td>'.$res['usetime'].'</td><td>'.($res['status']==1?'<font color="green">正常</font>':'<font color="red">离线</font>').'</td><td><a href="./add.php?id='.$res['id'].'" class="btn btn-xs btn-success">更新</a>&nbsp;<a href="javascript:checkAccount('.$res['id'].')" class="btn btn-xs btn-info">检测</a>&nbsp;<a href="./log.php?my=search&aid='.$res['id'].'" class="btn btn-xs btn-default">日志</a>&nbsp;<a href="javascript:deleteAccount('.$res['id'].')" class="btn btn-xs btn-danger">删除</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
<?php
echo '<ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
echo '<li><a href="list.php?page='.$first.$link.'">首页</a></li>';
echo '<li><a href="list.php?page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
echo '<li><a href="list.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
echo '<li><a href="list.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '';
if ($page<$pages)
{
echo '<li><a href="list.php?page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="list.php?page='.$last.$link.'">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
echo '</ul>';
?>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function checkAccount(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax.php?act=checkAccount',
		data : {id: id},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg,{
					icon: 1,
					closeBtn: false
				}, function(){
				  	window.location.reload()
				});
			}else{
				layer.alert(data.msg,{
					icon: 2,
					closeBtn: false
				}, function(){
				  	window.location.reload()
				});
			}
		}
	});
}
function deleteAccount(id){
	if(confirm('你确实要删除此账号吗？')){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax.php?act=deleteAccount',
			data : {id: id},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					alert('删除成功！');
					window.location.reload()
				}else{
					layer.alert(data.msg, {icon: 2})
				}
			}
		});
	}
}
</script>