<?php
/**
 * 媒体模型
 * Created by PhpStorm.
 * User: acer-pc
 * Date: 2017/10/4
 * Time: 11:15
 */

namespace app\model;

use think\Model;


class Media extends Model
{
    protected $table = 'vox_media';
    protected $pk = 'id';
    protected $fields = array(
        'id', 'type_id', 'name', 'url', 'status', 'create_time', 'update_time'
    );
    protected $type = [
        'id' => 'integer',
        'type_id' => 'integer',
        'status' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer'
    ];

    /**
     * 获取网址数量
     * @return mixed
     */
    public function getMedNumber()
    {
        $res = $this->field('count(id) as url')
            ->select();
        return $res[0]['url'];
    }

    /**
     * 获取网址列表
     * @param $cond_or
     * @param $cond_and
     * @param $order
     * @param array $pag
     * @return mixed
     */
    public function getMedList($cond_or, $cond_and,$order,$pag = [])
    {
        if (!isset($cond_and['a.status'])) {
            $cond_and['a.status'] = ['<>', 2];
            $cond_and['b.status'] = ['<>', 2];
        }
        if(empty($pag)){
            $pag = 10;
        }else if($pag == -1){
            $pag = $this->getMedNumber();
        }
        $res = $this->alias('a')->field('a.id as id, a.name as name ,
            b.id as type_id,b.name as type_name,a.url as url')
            ->join('vox_media_type b', 'a.type_id=b.id')
            ->whereor($cond_or)
            ->where($cond_and)
            ->order($order)
            ->paginate($pag);
        return $res;
    }

    /**
     * 获取本月网址增加数量
     */
    public function getPercentNumber()
    {
        $totalNum = $this->field('count(id) as t_num')
            ->select();
        $lastWeekUpdateNum = $this->field('count(id) as lw_num')
            ->wheretime('create_time', 'last week')
            ->select();
        $thisWeekUpdateNum = $this->field('count(id) as tw_num')
            ->wheretime('create_time', 'week')
            ->select();
        $thisYearUpdateNum = $this->field('count(id) as ty_num')
            ->wheretime('create_time', 'year')
            ->select();
        $thisMonthUpdateNum = $this->field('count(id) as tm_num')
            ->wheretime('create_time', 'month')
            ->select();
        $percent = $thisMonthUpdateNum[0]['tm_num'];
        return $percent;
    }

    /**
     * 更新网站信息
     * {@inheritDoc}
     * @see \think\Model::save()
     */
    public function saveData($id, $data)
    {
        $ret = [];
        $curtime = time();
        $data['update_time'] = $curtime;
        $errors = $this->filterField($data,true);
        $ret['errors'] = $errors;
        if (empty($errors)) {
            $this->save($data, ['id' => $id]);
        }
        return $ret;
    }

    /**
     * 添加网站
     * @param $data
     * @return array
     */
    public function addData($data)
    {
        $ret = [];
        $curTime = time();
        $data['create_time'] = $curTime;
        $errors = $this->filterField($data,false);
        $ret['errors'] = $errors;
        if (empty($errors)) {
            if (!isset($data['status']))
                $data['status'] = 1;
            $this->save($data);
        }
        return $ret;
    }

    /**
     * 过滤网站信息
     * @param $data
     * @param $isUpdate
     * @return array
     */
    private function filterField($data, $isUpdate)
    {
        $errors = [];
        if (isset($data['name']) && !$data['name']) {
            $errors['name'] = '网址名字不能为空';
        } else {
            if (!$isUpdate) {
                $cond_and = [];
                $cond_and['status'] = ['<>', 2];
                $cond_and['name'] = ['=',$data['name']];
                $list = $this->field('*')
                    ->where($cond_and)
                    ->find();
                if (!empty($list)) {
                    $errors['name'] = '网址名字不能重复';
                }
            }
        }
        if (isset($data['type_id']) && !$data['type_id']) {
            $errors['type_id'] = '网址类型不能为空';
        }
        if (isset($data['url']) && !$data['url']) {
            $errors['url'] = '网址不能为空';
        } else {
            if (!$isUpdate) {
                $cond_and = [];
                $cond_and['status'] = ['<>', 2];
                $cond_and['url'] = ['=',$data['url']];
                $list = $this->field('*')
                    ->where($cond_and)
                    ->find();
                if (!empty($list)) {
                    $errors['name'] = '网址不能重复';
                }
            }
        }
        return $errors;
    }

    /**
     * 根据id获取网址信息
     * @param $id
     * @return mixed
     */
    public function getById($id)
    {
        $res = $this->alias('a')->field('a.id as id, a.name as name ,
            b.id as type_id,b.name as type_name,a.url as url')
            ->join('vox_media_type b', 'a.type_id=b.id')
            ->where(['a.id' => $id])
            ->find();
        return $res;
    }

    /**
     * 删除操作
     * @param array $cond
     * @return false|int
     * @throws MyException
     */
    public function remove($cond = [])
    {
        $res = $this->save(['status' => 2], $cond);
        if ($res === false) throw new MyException('2', '删除失败');
        return $res;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getMediaTypeByName($name){
        $res = DB('media_type')->field('*')
            ->where(['name' => $name])
            ->find();
        return $res;
    }


    public function addMediaType($name){
        $insert_data = ['name' => $name,'status' => 1,'create_time' => time()];
        $res = Db('media_type')->insertGetId($insert_data);
        return $res;
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    public function import_Media($data){
        $media_type = $this->getMediaTypeByName($data['type_name']);
        $data_media = [];
        if (!empty($media_type)) {
            $data_media['type_id'] = $media_type['id'];
        }else{
            $res = $this->addMediaType($data['type_name']);
            $ret['res'] = $res;
            $data_media['type_id'] = $res;
        }
        $data_media['name'] = $data['name'];
        $data_media['url'] = $data['url'];
        $res = $this->addData($data_media);
        return $res;
    }
}