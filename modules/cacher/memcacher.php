<?
class Memcacher{
  public function __construct($config){
    $raw_hosts = (array)$config->cache_memcached_hosts;
  }
}

?>