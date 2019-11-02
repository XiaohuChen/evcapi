<?php


namespace App\Services;

use App\Exceptions\ArException;
use Illuminate\Support\Facades\DB;

class IndexService extends Service
{

    /**
     * @method 更新
     * @param string $version 版本号
     */
    public function Update($version){
        if(!is_numeric($version)) throw new ArException(ArException::PARAM_ERROR);
        $up = DB::table('UpdateInfo')->first();
        if(empty($up)) throw new ArException(ArException::SELF_ERROR,'No update');
        if(bccomp($version, $up->ver, 5) < 0) return $up;
        throw new ArException(ArException::SELF_ERROR,'No update');
    }

    /**
     * @method 快讯详情
     * 
     */
    public function NewsDetail(int $id){
        $news = DB::table('News')->where('Id', $id)->first();
        if(empty($news)) return [];
        $imgs = json_decode($news->Imgs);
        if(!is_array($imgs)) $imgs = [];
        else {
            foreach($imgs as $k => $img){
                $imgs[$k] = $img;
            }
        }
        DB::table('News')->where('Id', $id)->increment('ReadNum');
        $data = [
            'Id' => $news->Id,
            'Title' => $news->Title,
            'Imgs' => $imgs,
            'Content' => $news->Content,
            'AddTime' => $news->AddTime,
            'ReadNum' => $news->ReadNum
        ];
        return $data;
    }

    /**
     * @method 快讯列表
     * 
     */
    public function NewsList(int $count){
        $news = DB::table('News')->orderBy('Id','desc')->paginate($count);
        $list = [];
        foreach($news as $item){
            $imgs = json_decode($item->Imgs);
            if(!is_array($imgs)) $imgs = [];
            else {
                foreach($imgs as $k => $img){
                    $imgs[$k] = $img;
                }
            }
            $list[] = [
                'Id' => $item->Id,
                'Title' => $item->Title,
                'Imgs' => $imgs,
                'AddTime' => $item->AddTime,
                'ReadNum' => $item->ReadNum
            ];
        }
        return ['list' => $list, 'total' => $news->total()];
    }

    /**
     * @method banner列表
     */
    public function BannerList(){
        $banner = DB::table('Banner')
            ->where('IsDel',0)
            ->orderBy('Sort','desc')
            ->get();
        $list = [];
        foreach($banner as $item){
            $list[] = [
                'Id' => $item->Id,
                'Img' => $item->Image,
                'Title' => $item->Title,
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