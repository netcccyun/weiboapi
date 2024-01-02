<?php
include("../includes/common.php");
$title='操作日志';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-11 col-lg-10 center-block" style="float: none;">
<?php

if($_GET['my']=='search') {
	$sql=" `aid`='{$_GET['aid']}'";
	$link='&my=search&aid='.$_GET['aid'];
}else{
	$sql=" 1";
}
$numrows=$DB->getColumn("SELECT count(*) from weiboapi_log A LEFT JOIN weiboapi_account B ON A.aid=B.id WHERE{$sql}");

?>
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead><tr><th>ID</th><th>UID</th><th>昵称</th><th>操作类型</th><th>时间</th></tr></thead>
          <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT A.*,B.uid,B.nickname FROM weiboapi_log A LEFT JOIN weiboapi_account B ON A.aid=B.id WHERE{$sql} order by A.id desc limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '<tr><td><b>'.$res['id'].'</b></td><td>'.$res['uid'].'</td><td>'.$res['nickname'].'</td><td>'.$res['action'].'</td><td>'.$res['time'].'</td></tr>';
}
?>
          </tbody>
        </table>
      </div>
<?php
echo'<ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
echo '<li><a href="log.php?page='.$first.$link.'">首页</a></li>';
echo '<li><a href="log.php?page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
echo '<li><a href="log.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
echo '<li><a href="log.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '';
if ($page<$pages)
{
echo '<li><a href="log.php?page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="log.php?page='.$last.$link.'">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
echo'</ul>';
#分页
?>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function showreason(content){
	layer.alert(content, {title:'查看失败原因', shadeClose: true})
}
</script>