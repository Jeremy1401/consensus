<?php
/**
 * 任务主题关联模型
 * Created by PhpStorm.
 * User: acer-pc
 * Date: 2017/10/6
 * Time: 9:04
 */

namespace app\model;

use think\Model;

class TaskTheme extends Model
{
    protected $table = 'vox_task_theme';
    protected $fields = array(
        'task_id','theme_id', 'status', 'create_time', 'update_time'
    );
    protected $type = [
        'task_id' => 'integer',
        'theme_id' => 'integer',
        'status' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer'
    ];

    /**
     * 获取任务——主题列表
     * @param array $cond
     * @return mixed
     */
    public function getTaskThemeList($cond = []){
        $res = $this->field('id as type_id, name as type_name ')
            ->where("status <> '2'")
            ->select();
        return $res;
    }

    /**
     * 根据id获取任务——主题信息
     * @param $id
     * @return mixed
     */
    public function getById($id){
        $res = $this->field('*')
            ->where(['id' => $id])
            ->find();
        return $res;
    }

    /**
     * 更新任务——主题信息
     * {@inheritDoc}
     * @see \think\Model::save()
     */
    public function saveData($id, $data){
        $ret = [];
        $errors = $this->filterField($data);
        $ret['errors'] = $errors;
        if (empty($errors)) {
            $this->save($data, ['id' => $id]);
        }
        return $ret;
    }
    /**
     * 添加任务——主题信息
     * @param $data
     * @return array
     */
    public function addData($data){
        $ret = [];
        $time = $_SERVER['REQUEST_TIME'];
        $data['create_time'] = $time;
        $errors = $this->filterField($data);
        $ret['errors'] = $errors;
        if(empty($errors)) {
            if (!isset($data['status']))
                $data['status'] = 1;
            $this->save($data);
        }
        return $ret;
    }

    /**
     * 过滤任务——主题信息
     * @param $data
     * @return array
     */
    private function filterField($data){
        $errors = [];
        if (isset($data['task_id']) && !$data['task_id']) {
            $errors['task_id'] = '任务编号不能为空';
        }
        if (isset($data['theme_id']) && !$data['theme_id']) {
            $errors['theme_id'] = '采集主题不能为空';
        }
        return $errors;
    }
}