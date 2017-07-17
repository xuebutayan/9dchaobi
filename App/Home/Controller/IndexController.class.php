<?php
namespace Home\Controller;
use Common\Controller\CommonController;
class IndexController extends CommonController {
 	public function _initialize(){
 		parent::_initialize();
 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	public function index(){
		//页面右方公告，提示，资信
		$art_model = D('Article');
		$info1 = $art_model->info(1);
		$info_red1 = $art_model->info_red(1);//标红
		$info2 = $art_model->info(2);		 //非标红
		$info_red2 = $art_model->info_red(2);//标红
		$this->assign('info1',$info1);		 //非标红
		$this->assign('info_red1',$info_red1);
		$this->assign('info2',$info2);
		$this->assign('info_red2',$info_red2);

		//幻灯
		$flash=M('Flash')->order('sort')->limit(6)->select();
		$this->assign('flash',$flash);
		//币种
		$currency=$this->currency();
		foreach ($currency as $k=>$v){
			$list=$this->getCurrencyMessageById($v['currency_id']);
			$currency[$k]=array_merge($list,$currency[$k]);
			$list['new_price']?$list['new_price']:0;
			$currency[$k]['currency_all_money'] = floatval($v['currency_all_num'])*$list['new_price'];
		}
        //*********选择进盟币,安全可信赖begin*******
        $all_money = M('Trade')->sum('money');
        $all_money = $this->config['transaction_false']+$all_money;
//        $all_money = 212315984125.123;
        $all_money = (string)round($all_money);
        for($i=0;$i<strlen($all_money);$i++){
            $arr[strlen($all_money)-1-$i] = $all_money[$i];
//            $arr[$i] = $all_money[$i];
        }
        $this->assign('arr',$arr);
        //*********选择进盟币,安全可信赖end*******
        $link = M('Link');
        $link_info =$link->select();
        //截断友情链接url头，统一写法
        foreach($link_info as $k => $v){
        	$url="";
        	$url = trim($v['url'],'https://');
        	$link_info[$k]['url'] = trim($url,'http://');
        }
        //*******众筹begin*******/////
        $issue_list=M('Issue')
            ->field(C("DB_PREFIX")."issue.*,".C("DB_PREFIX")."currency.currency_name as name")
            ->join("left join ".C("DB_PREFIX")."currency on ".C("DB_PREFIX")."currency.currency_id=".C("DB_PREFIX")."issue.currency_id")
            ->order(' id desc ')->select();
        $this->assign('issue_list',$issue_list);
        //*******众筹end*******/////
        $sum_money = num_format($all_money);
        $this->assign('link_info',$link_info);
        $this->assign('sum_money',$sum_money);
		$this->assign('currency',$currency);
		$this->assign('empty','暂无数据');
        $this->display();
     }
     /*function chaxun(){
     	$member = M('Member');
     	$trade = M('Trade');
     	$re = $trade->group('member_id')->getField('member_id',true);
     	$trade_data = [];
     	//查询trade表
     	foreach ($re as $uid) {
     		$in = $trade->where(['member_id'=>$uid,'type'=>'buy','add_time'=>['gt',1492358400]])->sum('num');
     		$out = $trade->where(['member_id'=>$uid,'type'=>'sell','add_time'=>['gt',1492358400]])->sum('num');
     		$alert = $out>$in?'<font style="color:red">是</font>':'否';
     		$data = ['member_id'=>$uid,'in'=>$in,'out'=>$out,'alert'=>$alert];
     		$trade_data[]=$data;
     	}
     	$this->assign('trades',$trade_data);
     	$this->display();
     }*/
     function testa($aa=0,$bb=0){
        echo $aa.','.$bb;
     }
     function test($datetime=0){//exit;$member_id=0,$datetime=1482076800
        header('Content-type:text/html;Charset=utf-8');
        $now = time();//1482163200;//2016-12-20;
        $jintian = 1499788800;//strtotime(date('Y-m-d',$now));//想要停止统计的地方。
        $info = M('data')->field('member_id,datetime')->order('id desc')->find();

        if($info){
            if($datetime > $info['datetime']){//带参数
                $tongji_time = $datetime;
            }else{//不带参数，自动循环
                $where['m.member_id'] = ['gt',$info['member_id']];
                $tongji_time = $info['datetime'];
            }
        }else{
            $tongji_time = 1482076800;//2016-12-19
        }
        $start_time = strtotime(date('Y-m-d',$tongji_time));
        $end_time = $start_time + 86400;
        $where['m.reg_time'] = ['lt',$end_time];
        //查询用户数据
        $list = M('member m')->distinct('true')
        ->join('__CURRENCY_USER__ c on c.member_id=m.member_id and c.currency_id=30','LEFT')
        ->field('m.member_id,m.user_name,m.integrals,m.daily_inc,c.num')->where($where)->select();
        $total_list = M('member')->where(['reg_time'=>['lt',$end_time]])->count();

            $i=0;
            foreach ($list as $v) {
                //充值积分
                $czjf = M('member_log')->where(['member_id'=>$v['member_id'],'addtime'=>['between',$start_time.','.$end_time]])->sum('integrals');
                //释放积分
                $sfjf = M('integrals_log')->where(['member_id'=>$v['member_id'],'title'=>'大盘赠送','addtime'=>['between',$start_time.','.$end_time]])->sum('num');
                //转入积分
                $zrjf = M('integrals_log')->where(['member_id'=>$v['member_id'],'title'=>'积分转入','addtime'=>['between',$start_time.','.$end_time]])->sum('num');
                //前天积分
                $qt = M('data')->where(['member_id'=>$v['member_id'],'datetime'=>$start_time-86400])->find();
                //买币使用积分
                $mb = M('trade')->field('price,num,(price*num) as total')->where(['member_id'=>$v['member_id'],'add_time'=>['between',$start_time.','.$end_time],'type'=>'buy'])->select();
                $mbjf = 0;//买币积分
                $mbgd = '';//买币挂单
                $mbsl = 0;//买币数量
                foreach ($mb as $m) {
                    $mbjf += $m['total'];
                    $mbsl += $m['num'];
                    $mbgd .=$m['num'].'*'.$m['price'].'='.$m['total'].'<br>';
                }

                //充值ALPS
                $cz_alps1 = M('member_log')->where(['member_id'=>$v['member_id'],'addtime'=>['between',$start_time.','.$end_time]])->sum('num');
                $cz_alps2 = M('pay')->where(['member_id'=>$v['member_id'],'currency_id'=>30])->sum('money');
                //卖币挂单
                $nb = M('trade')->field('price,num,(price*num) as total')->where(['member_id'=>$v['member_id'],'add_time'=>['between',$start_time.','.$end_time],'type'=>'sell'])->select();
                $nbgd = '';//卖币挂单
                $nbsl = 0;//卖币数量
                $nbje = 0;//卖币金额
                foreach ($nb as $n) {
                    $nbje += $n['total'];
                    $nbsl += $n['num'];
                    $nbgd .= $n['num'].'*'.$n['price'].'='.$n['total'].'<br>';
                }
                //提现金额
                $money = M('withdraw')->where(['uid'=>$v['member_id'],'checktime'=>['between',$start_time.','.$end_time],'status'=>2])->sum('all_money');

                $data = [];
                $data['member_id'] = $v['member_id'];
                $data['user_name'] = $v['user_name'];
                $data['sftime'] = $now;
                $data['datetime'] = $start_time;
                $data['czjf'] = $czjf?$czjf:0;//充值积分
                $data['sfjf'] = $sfjf?$sfjf:0;//释放积分
                $data['zrjf'] = $zrjf?$zrjf:0;//转入积分
                $data['mbjf'] = $mbjf;//买币积分
                //$data['ztjf'] = $qt?($qt['sfjf']+$qt['zrjf']+$qt['czjf']+$qt['ztjf']-$mbjf):0;//昨日积分--昨日（释放积分+转入积分+充值积分+[昨日积分]）-买币使用积分=昨日积分;可注释掉
                //$data['kyjf'] = $data['czjf']+$data['sfjf']+$data['zrjf']+$data['ztjf'];//可用积分--今天释放积分+转入积分+充值积分+昨日积分=可用积分;可注释掉
                $data['mbgd'] = $mbgd;//买币挂单
                //$data['jfye'] = $data['kyjf']-$mbjf;//积分余额——可用积分-实际扣积分=积分余额;可注释掉
                $data['nbgd'] = $nbgd;//卖币挂单

                $data['zr_alps'] = $qt?$qt['ye_alps']:0;//昨日ALPS
                $data['cz_alps'] = $cz_alps1+$cz_alps2;//充值ALPS
                $data['km_alps'] = $data['zr_alps']+$mbsl;//可卖ALPS。
                $data['ye_alps'] = $data['km_alps']-$nbsl;//ALPS余额
                $data['zr_rmb'] = $qt?$qt['kt_rmb']:0;//昨日人民币
                $data['kt_rmb'] = $data['zr_rmb']+$nbje;//可提人民币
                $data['th_rmb'] = $data['kt_rmb']-$money;
                $data['status'] = 1;
                M('data')->add($data);
                $i++;
                if($i>300){
                    $total_done = M('data')->where(['datetime'=>$tongji_time])->count();
                    $this->redirect('Index/test',['datetime'=>$tongji_time],1,'统计日期'.date('Y-m-d',$tongji_time).',已完成'.intval($total_done/$total_list*100).'%。');
                }
            }

            $sj = $tongji_time+86400;
            if($sj>=$jintian) echo '统计完成。';
            else $this->redirect('Index/test',['datetime'=>$sj],1,'开始统计'.date('Y-m-d',$sj).'。');

     }


}