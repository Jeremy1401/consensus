<?php 
/**
 * 管理员账户模型
 * Author yzs
 * Create 2017.8.15
 */
namespace app\model;

use think\Model;
use think\Debug;

class UserAdmin extends Model{
 	protected $table = 'vox_user_admin';
 	protected $pk = 'id';
 	protected $fields = array(
        'id', 'logo', 'username', 'pass', 'role_id', 'name', 'photo', 'gender',
        'age', 'position', 'phone', 'email', 'address', 'postcode', 'remark',
        'status', 'login_time', 'create_time', 'update_time'
 	);
 	protected $type = [
 			'id' => 'integer',
 			'role_id' => 'integer',
 			'status' => 'integer'
 		];
 	const USER_TOKEN = 'admin_user_token';
 	const TOKEN_USER = 'admin_token_user';
 	
 	/**
 	 * 账号列表
 	 * @param array $cond
 	 */
 	public function getList($cond = []){
 		if(!isset($cond['status'])){
 			$cond['status'] = ['<>', 2];
 		}
 		return $this->field('id, logo, username, pass, role_id, name, photo, gender,
 		age, position, phone, email, address, postcode, remark,
        status, login_time, create_time')
            ->where($cond)
            ->select();
 	}

    /**
     * 根据ID获取账号
     * @param $id
     * @return mixed
     */
 	public function getById($id){
 		return $this->field('id, logo, username, pass, role_id, name, photo, gender,
 		age, position, phone, email, address, postcode, remark,
        status, login_time, create_time')
            ->where('id', $id)
            ->find();
 	}

    /**
     * 创建管理员用户
     * @param $data
     * @return false|int
     */
 	public function addData($data){
        if(!isset($data['status']))
            $data['status'] = 1;
 		$data['create_time'] = $data['update_time'] = $_SERVER['REQUEST_TIME'];
 		if(isset($data['pass']) && $data['pass']) $data['pass'] = md5($data['pass']);
 		return $this->save($data);
 	}

    /**
     * 编辑管理员用户
     * @param $id
     * @param $data
     * @return false|int
     */
 	public function saveData($id, $data){
 		$errors = $this->filterField($data);
        $ret['errors'] = $errors;
        if(empty($errors)){
            $data['update_time'] = $_SERVER['REQUEST_TIME'];
            if(isset($data['pass']) && $data['pass']) $data['pass'] = md5($data['pass']);
            $res = $this->save($data, ['id' => $id]);
            if(!$res){
                $ret['errors'] = ['msg' => '保存失败'];
            }

        }
 		return $ret;
 	}

    /**
     * 删除
     * @param array $cond
     * @return false|int
     * @throws MyException
     */
 	public function remove($cond = []){
 		$res = $this->save(['status' => 2], $cond);
 		if($res === false) throw new MyException('2', '删除失败');
 		return $res;
 	}

    /**
     * 根据token获取用户
     * @param $token
     * @return array|mixed
     */
 	public function getUserByToken($token){
 		if(!$token) return [];
 		return json_decode(cache_hash_hget(self::TOKEN_USER, $token), true);
 	}

    /**
     * 根据用户名获取用户
     * @param $username
     * @return mixed
     */
 	public function getUserByUsername($username){
 		return $this->field('id,logo,username,pass,status,role_id')
            ->where(['username' => $username, 'status' => ['<>', 2]])
            ->find();
 	}

    /**
     * 用户登录
     * @param $data
     * @throws MyException
     */
 	public function dologin($data){
 		if(empty($data['username'])) throw new MyException('用户名不能为空');
        $user = $this->getUserByUsername($data['username']);
        if(empty($user)) throw new MyException('用户不存在');
        if($user['status'] == 3) throw new MyException('用户已被禁用');
        if(md5($data['pass']) != $user['pass']) throw new MyException('密码错误');
        $this->recordLogin($user);
 	}

    /**
     * 登出
     * @param $token
     */
 	public function logout($token){
 		$this->recordLogout($token);
 	}
 	private function recordLogout($token){
 		if(!$token) return;
 		$user = json_decode(cache_hash_hget(self::TOKEN_USER, $token), true);
 		if(!empty($user)){
 			cache_hdel(self::TOKEN_USER, $token);
	 		$tokens = json_decode(cache_hash_hget(self::USER_TOKEN, $user['id']), true);
	 		if(!empty($tokens)){
	 			$k = array_search($token, $tokens);
	 			if(!is_null($k)){
	 				unset($tokens[$k]);
	 				cache_hash_hset(self::USER_TOKEN, $token, json_encode($tokens));
	 			}
	 		}
 		}
 		session('token', null);
 	}

    /**
     * 登录记录
     * @param $user
     * @throws MyException
     */
 	private function recordLogin($user){
 		$token = $this->generateToken($user['id']);
 		//存储用户-token
 		$tokens = json_decode(cache_hash_hget(self::USER_TOKEN, $user['id']), true);
 		if(empty($tokens)){
 			$tokens = [];
 		}
 		array_push($tokens, $token);
 		cache_hash_hset(self::USER_TOKEN, $user['id'], json_encode($tokens));
 		//存储token-用户
 		$data = [
 			'id' => $user['id'],
 			'create_time' => $_SERVER['REQUEST_TIME'],
 			'username'  => $user['username'],
 			'role_id' => $user['role_id']
 		];
 		cache_hash_hset(self::TOKEN_USER, $token, json_encode($data));
 		$res = $this->save(['login_time' => $_SERVER['REQUEST_TIME'], 'update_time' => $_SERVER['REQUEST_TIME']], ['id' => $user['id']]);
 		if(!$res) throw new MyException('登录失败');
 		session('token', $token);
 	}

    /**
     * 生成Token
     * @param $id
     * @return string
     * @throws \Exception
     */
 	private function generateToken($id){
 		if(!$id) throw new \Exception('创建token失败');
 		$rand = $_SERVER['REQUEST_TIME'].rand(0, 1000);
 		return md5($id.$rand);
 	}

    /**
     * 检查密码是否正确
     * @param $user_id
     * @param $pass
     * @return bool
     */
    public function checkPass($user_id, $pass){
        $res['errors'] = [];
        $user = $this->getById($user_id);
        if(md5($pass) != $user['pass']){
            $res['errors'] = ['pass' => '密码错误'];
        }
        return $res;
    }

    /**
     * 过滤必要字段
     * @param $data
     * @return array
     */
    private function filterField($data){
        $errors = [];
        if(isset($data['pass']) && !$data['pass']){
            $errors['pass'] = '密码不能为空';
        }
        if(isset($data['name']) && !$data['name']){
            $errors['name'] = '名字不能为空';
        }
        if(isset($data['gender']) && !$data['gender']){
            $errors['gender'] = '性别不能为空';
        }
        if(isset($data['age']) && !$data['age']){
            $errors['age'] = '年龄不能为空';
        }
        if(isset($data['position']) && !$data['position']){
            $errors['position'] = '职称不能为空';
        }
        if(isset($data['phone']) && !$data['phone']){
            $errors['phone'] = '联系方式不能为空';
        }
        if(isset($data['email']) && !$data['email']){
            $errors['email'] = '常用邮箱不能为空';
        }
        if(isset($data['address']) && !$data['address']){
            $errors['address'] = '联系地址不能为空';
        }
        return $errors;
    }
 }
?>