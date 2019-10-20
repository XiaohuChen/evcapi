<?php


namespace App\Services;

use App\Exceptions\ArException;
use Illuminate\Support\Facades\DB;

class IndexService extends Service
{

    /**
     * @method banner列表
     */
    public function BannerList(){
        $banner = DB::table('Banner')
            ->where('IsDel',0)
            ->orderBy('Sort','desc')
            ->get();
        $list = [];
        $domain = '';
        $qiniu = DB::table('QiniuConfig')->first();
        if(!empty($qiniu)) $domain = $qiniu->Domain;
        foreach($banner as $item){
            $list[] = [
                'Id' => $item->Id,
                'Img' => $domain.$item->Image,
                'Title' => $item->Title,
                'Url' => $item->Url
            ];
        }
        return $list;
    }

    /**
     * @method 消息详情
     * @param int $id 消息ID
     */
    public function NoticeDetail(int $id){
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);
        $notice = DB::table('Notice')->where('Id', $id)->first();
        if(empty($notice)) return [];
        $deital = [
            'Id' => $notice->Id,
            'Title' => $notice->Title,
            'AddTime' => $notice->AddTime,
            'Content' => $notice->Content
        ];
        return $deital;
    }

    /**
     * @method 消息列表
     * @param int $count 分页参数
     */
    public function NoticeList(int $count){
        if($count <= 0) throw new ArException(ArException::PARAM_ERROR);
        $notices = DB::table('Notice')
            ->where('IsDel', 0)
            ->orderBy('Id','desc')
            ->paginate($count);
        $list = [];
        foreach($notices as $item){
            $list[] = [
                'Id' => $item->Id,
                'Title' => $item->Title,
                'AddTime' => $item->AddTime,
                //'IsRead' => $item->IsRead //是否已读，需要再开启
            ];
        }
        return $list;
    }
    
}