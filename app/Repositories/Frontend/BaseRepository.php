<?php
namespace App\Repositories\Frontend;

use App\Models\Dict;
use App\Models\UserOperateRecord;
use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;

abstract class BaseRepository
{
    protected static $instance;
    const ERROR_STATUS   = 0; // 失败状态
    const SUCCESS_STATUS = 1; // 成功状态
    protected $current_model; // Model类

    //获取实例化
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new $class;
        }
        return self::$instance[$class];
    }

    /**
     * 过滤参数
     * @param  Array $params
     * @return Array
     */
    public function parseParams($params)
    {
        if (empty($params)) {
            return [];
        }
        $field_lists = Schema::getColumnListing($this->table_name);
        foreach ($params as $key => $value) {
            if (!in_array($key, $field_lists)) {
                unset($params[$key]);
                break;
            }
            if (empty($value) && !($value === '0' || $value === 0)) {
                unset($params[$key]);
            }
        }
        return $params;
    }

    /**
     * 获取字典
     * @param  Array $code_arr
     * @return Array
     */
    protected function getDicts($code_arr)
    {
        $result = [];
        if (!empty($code_arr) && is_array($code_arr)) {
            $lists = Dict::whereIn('code', $code_arr)->get();
            foreach ($code_arr as $item) {
                $result[$item] = $lists->where('code', $item)->values()->toArray();
            }
        }
        return $result;
    }

    /**
     * 记录操作日志
     * @param  Array  $input [action, params, text, status]
     * @return Array
     */
    public function saveUserOperateRecord($input)
    {
        try {
            UserOperateRecord::create([
                'user_id'   => Auth::guard('web')->id(),
                'action'     => $input['action'],
                'params'     => json_encode($input['params']),
                'text'       => $input['text'],
                'ip_address' => getClientIp(),
                'status'     => $input['status'],
            ]);
        } catch (Exception $e) {
            Log::info('RECORD FAIL：saveUserOperateRecord is error，params :' . json_encode($input));
        }
    }
}
