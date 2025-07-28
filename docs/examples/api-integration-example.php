<?php

namespace YourPlugin\Services;

use AdzHive\helpers\RESTHelper;
use AdzHive\Config;
use AdzHive\Exception;

/**
 * Example API Integration Service
 * 
 * This example shows how to integrate with external APIs using the RESTHelper
 * with proper error handling, caching, and configuration management.
 */
class ExternalAPIService 
{
    protected $config;
    protected $apiKey;
    protected $baseUrl;
    protected $timeout;
    
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->apiKey = $this->config->get('api.external_service.key');
        $this->baseUrl = $this->config->get('api.external_service.base_url', 'https://api.example.com');
        $this->timeout = $this->config->get('api.external_service.timeout', 30);
        
        if (!$this->apiKey) {
            throw new Exception('External API key not configured');
        }
    }
    
    /**
     * Get user data from external API
     */
    public function getUser($userId)
    {
        $cacheKey = 'external_user_' . $userId;
        $cachedData = get_transient($cacheKey);
        
        if ($cachedData !== false) {
            return $cachedData;
        }
        
        try {
            $api = new RESTHelper($this->baseUrl . '/users/' . $userId);
            $api->setHeader('Authorization', 'Bearer ' . $this->apiKey)
                ->setHeader('Accept', 'application/json')
                ->setOption(CURLOPT_TIMEOUT, $this->timeout);
            
            $response = $api->get();
            
            if (!$response->isSuccess()) {\n                throw new Exception(\n                    'API request failed: ' . $response->getHttpCode(), \n                    $response->getHttpCode()\n                );\n            }\n            \n            $userData = $response->getResult();\n            \n            // Cache for 1 hour\n            set_transient($cacheKey, $userData, HOUR_IN_SECONDS);\n            \n            adz_log_info('User data fetched successfully', [\n                'user_id' => $userId,\n                'response_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']\n            ]);\n            \n            return $userData;\n            \n        } catch (\\Exception $e) {\n            adz_log_error('Failed to fetch user data', [\n                'user_id' => $userId,\n                'error' => $e->getMessage(),\n                'http_code' => $api->getHttpCode() ?? 'unknown'\n            ]);\n            \n            throw new Exception('Unable to fetch user data: ' . $e->getMessage());\n        }\n    }\n    \n    /**\n     * Create a new user via API\n     */\n    public function createUser($userData)\n    {\n        try {\n            $api = new RESTHelper($this->baseUrl . '/users');\n            $api->setBearerToken($this->apiKey)\n                ->setHeader('Content-Type', 'application/json')\n                ->setOption(CURLOPT_TIMEOUT, $this->timeout);\n            \n            $response = $api->post(null, $userData);\n            \n            if (!$response->isSuccess()) {\n                $error = $response->getResult();\n                throw new Exception(\n                    'Failed to create user: ' . ($error['message'] ?? 'Unknown error'),\n                    $response->getHttpCode()\n                );\n            }\n            \n            $newUser = $response->getResult();\n            \n            adz_log_info('User created successfully', [\n                'new_user_id' => $newUser['id'] ?? 'unknown',\n                'email' => $userData['email'] ?? 'unknown'\n            ]);\n            \n            return $newUser;\n            \n        } catch (\\Exception $e) {\n            adz_log_error('Failed to create user', [\n                'user_data' => $userData,\n                'error' => $e->getMessage()\n            ]);\n            \n            throw new Exception('Unable to create user: ' . $e->getMessage());\n        }\n    }\n    \n    /**\n     * Update user data\n     */\n    public function updateUser($userId, $userData)\n    {\n        try {\n            $api = new RESTHelper($this->baseUrl . '/users/' . $userId);\n            $api->setBearerToken($this->apiKey)\n                ->setHeader('Content-Type', 'application/json');\n            \n            $response = $api->put(null, $userData);\n            \n            if (!$response->isSuccess()) {\n                throw new Exception(\n                    'Failed to update user',\n                    $response->getHttpCode()\n                );\n            }\n            \n            // Clear cache\n            delete_transient('external_user_' . $userId);\n            \n            $updatedUser = $response->getResult();\n            \n            adz_log_info('User updated successfully', [\n                'user_id' => $userId,\n                'updated_fields' => array_keys($userData)\n            ]);\n            \n            return $updatedUser;\n            \n        } catch (\\Exception $e) {\n            adz_log_error('Failed to update user', [\n                'user_id' => $userId,\n                'error' => $e->getMessage()\n            ]);\n            \n            throw new Exception('Unable to update user: ' . $e->getMessage());\n        }\n    }\n    \n    /**\n     * Delete user\n     */\n    public function deleteUser($userId)\n    {\n        try {\n            $api = new RESTHelper($this->baseUrl . '/users/' . $userId);\n            $api->setBearerToken($this->apiKey);\n            \n            $response = $api->delete();\n            \n            if (!$response->isSuccess() && $response->getHttpCode() !== 404) {\n                throw new Exception(\n                    'Failed to delete user',\n                    $response->getHttpCode()\n                );\n            }\n            \n            // Clear cache\n            delete_transient('external_user_' . $userId);\n            \n            adz_log_info('User deleted successfully', ['user_id' => $userId]);\n            \n            return true;\n            \n        } catch (\\Exception $e) {\n            adz_log_error('Failed to delete user', [\n                'user_id' => $userId,\n                'error' => $e->getMessage()\n            ]);\n            \n            throw new Exception('Unable to delete user: ' . $e->getMessage());\n        }\n    }\n    \n    /**\n     * Batch operations with retry logic\n     */\n    public function batchCreateUsers($users, $maxRetries = 3)\n    {\n        $results = [];\n        $failed = [];\n        \n        foreach ($users as $index => $userData) {\n            $attempts = 0;\n            \n            while ($attempts < $maxRetries) {\n                try {\n                    $result = $this->createUser($userData);\n                    $results[] = $result;\n                    break;\n                    \n                } catch (\\Exception $e) {\n                    $attempts++;\n                    \n                    if ($attempts >= $maxRetries) {\n                        $failed[] = [\n                            'index' => $index,\n                            'data' => $userData,\n                            'error' => $e->getMessage()\n                        ];\n                        \n                        adz_log_warning('Max retries reached for user creation', [\n                            'user_index' => $index,\n                            'attempts' => $attempts,\n                            'error' => $e->getMessage()\n                        ]);\n                    } else {\n                        // Wait before retry (exponential backoff)\n                        sleep(pow(2, $attempts - 1));\n                    }\n                }\n            }\n        }\n        \n        return [\n            'successful' => $results,\n            'failed' => $failed,\n            'summary' => [\n                'total' => count($users),\n                'successful' => count($results),\n                'failed' => count($failed)\n            ]\n        ];\n    }\n    \n    /**\n     * Health check for the external API\n     */\n    public function healthCheck()\n    {\n        try {\n            $api = new RESTHelper($this->baseUrl . '/health');\n            $api->setBearerToken($this->apiKey)\n                ->setOption(CURLOPT_TIMEOUT, 10);\n            \n            $response = $api->get();\n            \n            $isHealthy = $response->isSuccess();\n            $responseTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];\n            \n            $status = [\n                'healthy' => $isHealthy,\n                'response_time' => $responseTime,\n                'http_code' => $response->getHttpCode(),\n                'timestamp' => current_time('mysql')\n            ];\n            \n            if ($isHealthy) {\n                adz_log_info('API health check passed', $status);\n            } else {\n                adz_log_warning('API health check failed', $status);\n            }\n            \n            return $status;\n            \n        } catch (\\Exception $e) {\n            $status = [\n                'healthy' => false,\n                'error' => $e->getMessage(),\n                'timestamp' => current_time('mysql')\n            ];\n            \n            adz_log_error('API health check error', $status);\n            \n            return $status;\n        }\n    }\n    \n    /**\n     * Get API usage statistics\n     */\n    public function getUsageStats()\n    {\n        try {\n            $api = new RESTHelper($this->baseUrl . '/usage');\n            $api->setBearerToken($this->apiKey);\n            \n            $response = $api->get();\n            \n            if (!$response->isSuccess()) {\n                throw new Exception('Failed to fetch usage stats');\n            }\n            \n            return $response->getResult();\n            \n        } catch (\\Exception $e) {\n            adz_log_error('Failed to fetch API usage stats', [\n                'error' => $e->getMessage()\n            ]);\n            \n            return null;\n        }\n    }\n}