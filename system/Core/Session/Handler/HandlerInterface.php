<?php
namespace Core\Session\Handler;

interface HandlerInterface
{

    public function open($save_path, $session_id);

    public function close();

    public function read($session_id);

    public function write($session_id, $session_data);

    public function destroy($session_id);

    public function gc($maxlifetime);
}