<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
class IndexController extends AdminController {
    public function _initialize(){
        parent::_initialize();
    }
    //空操作
    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:404');
    }
    public function index(){
        //币种信息
//        $list=M('Currency')->select();
//        foreach ($list as $k=>$v){
//            $currency_message=$this->getCurrencyMessageById($v['currency_id']);
//            $list[$k]=array_merge($currency_message,$list[$k]);
//            $list[$k]['balance']=$this->get_qianbao_balance($v['port_number']);
//        }
//
//        $this->assign('list',$list);
//        $this->assign('empty','暂无数据');
        $this->display();
    }

    /**
     * 统计全站信息
     */
    public function infoStatistics(){
        $baodan = M('Baodan');
        //统计全站信息
        //送过来积分总数
        $jifen = $baodan->sum('integral');
        //应释放总数
        $true_jifen = $baodan->where('remain_days>0')->sum('integral');
        $true_jifen = $true_jifen*0.005;
        //总人数
        $member_count=M('Member')->count();
        //众筹总数量
        $issue_count=M('issue')->field("sum(num)-sum(deal) as count")->find();
        //人民币收入
        $pay_money_count = M('pay')->where("status = 1 ")->sum('count');
        //人民币支出
        $withdraw_money_count = M('withdraw')->where(" status = 2")->sum("money");
        //充值单数
        $pay_count = M('pay')->where("status = 1 ")->count();
        //提现单数
        $withdraw_count = M('withdraw')->where(" status = 2")->count();
        //全站币种统计
        $currency_u_info = M('currency')
                        ->alias('a')
                        ->field('a.currency_name,sum(b.num) as num,sum(b.forzen_num) as forzen_num')
                        ->join("left join ".C("DB_PREFIX")."currency_user AS b on a.currency_id = b.currency_id")
                        ->group('a.currency_id')
                        ->select();
        //今日应报单人数
        $count = $baodan->field('user_id,nextupdate')->group("`user_id`")->order('user_id')->select();
        //今日报完人数
        $baowan = $this->baowan();
        //alps编号数量
        $alps_num = M('Member')->where(" alps_code <> '' ")->count();
        $this->assign('alps_num',$alps_num);
        $this->assign('baodan_count',count($count));
        $this->assign('baowan',$baowan);
        $this->assign('member',$member_count);
        $this->assign('issue_count',$issue_count);
        $this->assign('pay_money_count',$pay_money_count);
        $this->assign('withdraw_money_count',$withdraw_money_count);
        $this->assign('pay_count',$pay_count);
        $this->assign('withdraw_count',$withdraw_count);
        $this->assign('currency_u_info',$currency_u_info);
        $this->assign('jifen',$jifen);
        $this->assign('true_jifen',$true_jifen);
        $this->display();
    }

    /**
     * 删除缓存方法
     */
    public function cache(){
        $cacheDir = $_POST['type'];
        $type = $cacheDir;
        //将传递过来的值进行切割，我是已“-”进行切割的
        $name = explode('-', $type);
        //得到切割的条数，便于下面循环
        $count = count($name);
        //循环调用上面的方法
        for ($i = 0; $i < $count; $i++)
        {
            //得到文件的绝对路径
            $abs_dir = dirname(dirname(dirname(dirname(__FILE__))));
            //组合路径
            $pa = $abs_dir . str_replace("/", "\\", str_replace("./", "\\", RUNTIME_PATH)); //得到运行时的目录
            $runtime = $pa . 'common~runtime.php';
            if (file_exists($runtime))//判断 文件是否存在
            {
                unlink($runtime); //进行文件删除
            }
            //调用删除文件夹下所有文件的方法
            $this->rmFile($pa, $name[$i]);
        }
        $data['status'] = 1;
        $data['info'] = "清理成功";
        $this->ajaxReturn($data);
    }

    /**
     * 删除文件和目录
     * @param type $path 要删除文件夹路径
     * @param type $fileName 要删除的目录名称
     */
    private function rmFile($path, $fileName)
    {//删除执行的方法
        //去除空格
        $path = preg_replace('/(\/){2,}|{\\\}{1,}/', '/', $path);
        //得到完整目录
        $path.= $fileName;
        //判断此文件是否为一个文件目录
        if (is_dir($path))
        {
            //打开文件
            if ($dh = opendir($path))
            {
                //遍历文件目录名称
                while (($file = readdir($dh)) != false)
                {
                    $sub_file_path = $path . "\\" . $file;
                    if ("." == $file || ".." == $file)
                    {
                        continue;
                    }
                    if (is_dir($sub_file_path))
                    {
                        $this->rmFile($sub_file_path, "");
                        rmdir($sub_file_path);
                    }
                    //逐一进行删除
                    unlink($sub_file_path);
                }
                //关闭文件
                closedir($dh);
            }
            rmdir($sub_file_path);//删除当前目录
        }
    }
    //积分任务
    function jifen(){
        $this->display();
    }
    function reset(){
        F('mission',0);
        M('Member')->where(1)->save(['bao'=>0]);
        $this->success('重置成功！');
    }
    function doCreateCode(){
        $member = M('Member');
        $p = !empty($_GET['p'])?$_GET['p']:1;
        $total = $member->field('member_id')->where(" alps_code='' ")->select();
        $total = count($total);//总人数
        $pagesize = 50;//50人执行一次
        $pages = ceil($total/$pagesize);//分页数
        $start = ($p-1)*$pagesize;//起始数
        if($p>$pages){
            $this->ajaxReturn(['info'=>'任务执行完成！','status'=>0]);
        }
        $udata = $member->field('member_id')->where(" alps_code='' ")->order('member_id')->limit($start.','.$pagesize)->select();
        foreach ($udata as $u) {
            do{
                $alps_code = 'AL'.rand(100000,999999);
                $member_id = $member->where(['alps_code'=>$alps_code])->getField('member_id');
            }while($member_id);
            $re = $member->where("member_id = {$u['member_id']}")->save(['alps_code'=>$alps_code]);
        }
        $percent = intval($p*$pagesize*(100/$total));
        $this->ajaxReturn(['info'=>'当前执行完成'.(($percent<=100)?$percent:100).'%','status'=>1]);
    }
    function doJifen(){
        $baodan = M('Baodan');
        $member = M('Member');
        $p = !empty($_GET['p'])?$_GET['p']:1;
        $f = (isset($_GET['x'])||isset($_GET['y']))?1:0;
        if($f){
            empty($_GET['x']) && $this->ajaxReturn(['status'=>0,'info'=>'参数不能为空']);
            empty($_GET['y']) && $this->ajaxReturn(['status'=>0,'info'=>'参数不能为空']);
            $x = intval($_GET['x']);
            $y = intval($_GET['y']);
            if($x>$y) $this->ajaxReturn(['status'=>0,'info'=>'起始顺序错误！']);
        }
        //删除状态为0的任务
        $baodan->where(array('status'=>0))->delete();//dump($_GET);exit;
        //$baodan->where(array('remain_days'=>0))->delete();
        if($x){
            $total = $baodan->where(['user_id'=>[['egt',$x],['elt',$y]]])->field('user_id,nextupdate')->group("`user_id`")->order('user_id')->select();
        }
        else $total = $baodan->field('user_id,nextupdate')->group("`user_id`")->order('user_id')->select();
        $total = count($total);//总人数
        $pagesize = 30;//50人执行一次
        $pages = ceil($total/$pagesize);//分页数
        $start = ($p-1)*$pagesize;//起始数
        if($f){
            $udata = $baodan->where(['user_id'=>[['egt',$x],['elt',$y]]])->field('user_id,nextupdate')->group("`user_id`")->order('user_id')->limit($start.','.$pagesize)->select();
        }
        else $udata = $baodan->field('user_id,nextupdate')->group("`user_id`")->order('user_id')->limit($start.','.$pagesize)->select();

        if($udata)
        foreach ($udata as $u) {
            $data = array();
            $data['user_id'] = $u['user_id'];
            $member_info = $member->field('member_id,bao')->where($data)->find();
            if($member_info['bao']==1) continue;//已报过单防止重复

            $member_id = $member_info['member_id'];
            $list = $baodan->where($data)->order('posttime asc')->select();

            if($list){
                $jifen = 0;
                $times = [];
                foreach ($list as $v) {
                    if($v['remain_days']){
                    	$jifen += $v['integral']*0.005;
                    	$baodan->where('oid='.$v['oid'])->setDec('remain_days');
                    }
                    $times[] = $v['remain_days']?(201-$v['remain_days']):200;
                }
                //检测是否有贷款用户
                $borrow = M('Borrow');
                $info = $borrow->where(['status'=>2,'member_id'=>$member_id])->find();
                $cha = $info['money']*1.2-$info['paymoney'];
                $o_jifen = $jifen;

                if($member_id && $jifen) $this->inte_log($member_id,$o_jifen,1,'大盘赠送');

                if($info && $cha>0){
                    //当日还款数额小于当日释放积分的60%
                    if($cha < $jifen*0.6){
                        $jifen = $jifen-$cha;
                        $this->inte_log($member_id,$cha,3,'大盘扣减');
                        $borrow->where(['member_id'=>$member_id])->save(['status'=>3,'paymoney'=>['exp','paymoney+'.$cha]]);
                    }else{
                        $re = $borrow->where(['member_id'=>$member_id])->setInc('paymoney',$jifen*0.6);
                        $this->inte_log($member_id,$jifen*0.6,3,'大盘扣减');
                        $jifen = $jifen*0.4;
                    }
                }
                $times = implode(',',$times);
                $member->where(array('user_id'=>$u['user_id']))->save(['integrals'=>['exp','integrals+'.$jifen],'daily_inc'=>$o_jifen,'send_times'=>$times,'bao'=>1]);
            }
        }
        if($p>$pages){
            if(!$f) F('mission',1);
            $this->ajaxReturn(['info'=>'任务执行完成！','status'=>0]);
        }
        $percent = intval($p*$pagesize*(100/$total));
        $this->ajaxReturn(['info'=>'当前执行完成'.(($percent<=100)?$percent:100).'%','status'=>1]);
    }
    function test(){
        echo date('Y-m-d',time());
    }
    function baowan(){
        //报单的总人数
        $baodan = M('Baodan');
        $udata = $baodan->field('user_id,nextupdate')->group("`user_id`")->select();
        $timestamp = strtotime(date('Y-m-d',time()));//今天
        //已赠送人的user_id数组
        $baowan = M('integrals_log i')
        ->field('m.user_id')
        ->join('__MEMBER__ m on i.member_id=m.member_id','LEFT')
        ->where(['title'=>'大盘赠送','addtime'=>['gt',$timestamp]])->order('id desc')->getField('user_id',true);
        $re = array_count_values($baowan);
        if($_GET['detail']==1){dump($re);exit;}
        return count($re);
    }
    function bubao(){
        if(!F('mission')){echo '尚未执行积分发放';exit;}
        $member = M('Member');
        //报单的总人数
        $baodan = M('Baodan');
        $udata = $baodan->field('user_id,nextupdate')->group("`user_id`")->select();
        $timestamp = strtotime(date('Y-m-d',time()));//今天
        //已赠送人的user_id数组
        $baowan = M('integrals_log i')
        ->field('m.user_id')
        ->join('__MEMBER__ m on i.member_id=m.member_id','LEFT')
        ->where(['title'=>'大盘赠送','addtime'=>['gt',$timestamp]])->order('id desc')->getField('user_id',true);
        $baowan = array_unique($baowan);
        if(count($baowan)==count($udata)){echo '已全部发放完毕';exit;}
        //$baowan1 = array_count_values($baowan);//可以计算出一个人一天发放几次
        //补报单
        foreach ($udata as $u) {
            if(in_array($u['user_id'],$baowan)) continue;
            //if($baowan1[$u['user_id']] > 2) continue;
            $data = array();
            $data['user_id'] = $u['user_id'];
            $list = $baodan->where($data)->order('posttime asc')->select();
            $member_id = $member->where($data)->getField('member_id');
            if($list){
                $jifen = 0;
                $times = [];
                foreach ($list as $v) {
                    if($v['remain_days']){
                        $jifen += $v['integral']*0.005;
                        $baodan->where('oid='.$v['oid'])->setDec('remain_days');
                    }
                    $times[] = $v['remain_days']?(201-$v['remain_days']):200;
                }
                //检测是否有贷款用户
                $borrow = M('Borrow');
                $info = $borrow->where(['status'=>2,'member_id'=>$member_id])->find();
                $cha = $info['money']-$info['paymoney'];
                $o_jifen = $jifen;

                if($member_id && $jifen) $this->inte_log($member_id,$o_jifen,1,'大盘赠送');

                if($info && $cha>0){
                    //当日还款数额小于当日释放积分的60%
                    if($cha < $jifen*0.6){
                        $jifen = $jifen-$cha;
                        $this->inte_log($member_id,$cha,3,'大盘扣减');
                        $borrow->where(['member_id'=>$member_id])->save(['status'=>3,'paymoney'=>$info['money']]);
                    }else{
                        $re = $borrow->where(['member_id'=>$member_id])->setInc('paymoney',$jifen*0.6);
                        $this->inte_log($member_id,$jifen*0.6,3,'大盘扣减');
                        $jifen = $jifen*0.4;
                    }
                }
                $times = implode(',',$times);
                $member->where(array('user_id'=>$u['user_id']))->save(['integrals'=>['exp','integrals+'.$jifen],'daily_inc'=>$o_jifen,'send_times'=>$times,'bao'=>1]);
            }
        }
        echo '完成';
    }
    function bubao_teding(){
        $member = M('Member');
        //报单的总人数
        $baodan = M('Baodan');
        $udata = [['user_id'=>456],['user_id'=>457]];
        //补报单
        foreach ($udata as $u) {
            //if(in_array($u['user_id'],$baowan)) continue;
            //if($baowan1[$u['user_id']] > 2) continue;
            $data = array();
            $data['user_id'] = $u['user_id'];
            $list = $baodan->where($data)->order('posttime asc')->select();
            $member_id = $member->where($data)->getField('member_id');
            if($list){
                $jifen = 0;
                $times = [];
                foreach ($list as $v) {
                    if($v['remain_days']){
                        $jifen += $v['integral']*0.005;
                        $baodan->where('oid='.$v['oid'])->setDec('remain_days');
                    }
                    $times[] = $v['remain_days']?(201-$v['remain_days']):200;
                }
                //检测是否有贷款用户
                $borrow = M('Borrow');
                $info = $borrow->where(['status'=>2,'member_id'=>$member_id])->find();
                $cha = $info['money']-$info['paymoney'];
                $o_jifen = $jifen;

                if($member_id && $jifen) $this->inte_log($member_id,$o_jifen,1,'大盘赠送');

                if($info && $cha>0){
                    //当日还款数额小于当日释放积分的60%
                    if($cha < $jifen*0.6){
                        $jifen = $jifen-$cha;
                        $this->inte_log($member_id,$cha,3,'大盘扣减');
                        $borrow->where(['member_id'=>$member_id])->save(['status'=>3,'paymoney'=>$info['money']]);
                    }else{
                        $re = $borrow->where(['member_id'=>$member_id])->setInc('paymoney',$jifen*0.6);
                        $this->inte_log($member_id,$jifen*0.6,3,'大盘扣减');
                        $jifen = $jifen*0.4;
                    }
                }
                $times = implode(',',$times);
                $member->where(array('user_id'=>$u['user_id']))->save(['integrals'=>['exp','integrals+'.$jifen],'daily_inc'=>$o_jifen,'send_times'=>$times,'bao'=>1]);
            }
        }
        echo '完成';
    }
    function ceshi(){
        $total = 208;
        $total_page = 5;
        $p = $_GET['p'];
        if($p>$total_page) $this->ajaxReturn(['info'=>'任务执行完成！','status'=>0]);
        $percent = intval($p*50*(100/$total));
        $data = [
            'info'=>'当前执行完成'.(($percent<=100)?$percent:100).'%',
            'status'=>1
        ];
        $this->ajaxReturn($data);
    }
}