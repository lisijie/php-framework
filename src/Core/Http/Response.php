<?php
namespace Core\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * HTTP响应
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core\Http
 */
class Response extends Message implements ResponseInterface
{
    const EOL = "\r\n";

    /**
     * @var int
     */
    protected $status = 200;

    /**
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * http状态码
     * @var array
     */
    protected static $httpCodes = [
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported'
    ];

    public function __construct($status = 200, $body = null, Headers $headers = null, $version = '1.1', $reasonPhrase = '')
    {
        $this->status = $status;
        if (empty($reasonPhrase) && isset(static::$httpCodes[$status])) {
            $this->reasonPhrase = static::$httpCodes[$status];
        } else {
            $this->reasonPhrase = (string)$reasonPhrase;
        }
        $this->protocol = $version;
        $this->headers = $headers ? $headers : new Headers();
        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                $this->headers->set($name, $value);
            }
        }
        $this->body = $this->createStream($body);
    }

    /**
     * 根据body类型创建Stream对象
     *
     * @param $body
     * @return Stream
     */
    private function createStream($body)
    {
        // integer、float、string 或 boolean
        if (is_scalar($body)) {
            $stream = fopen('php://temp', 'r+');
            if ($body !== '') {
                fwrite($stream, $body);
                fseek($stream, 0);
            }
            return new Stream($stream);
        }
        switch (gettype($body)) {
            case 'resource':
                return new Stream($body);
            case 'object':
                if (method_exists($body, '__toString')) {
                    return $this->createStream((string)$body);
                }
                break;
            case 'NULL':
                return new Stream(fopen('php://temp', 'r+'));
        }
        throw new \InvalidArgumentException('Invalid resource type: ' . gettype($body));
    }

    /**
     * 返回响应状态码
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * 返回指定状态码的新对象
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $obj = clone $this;
        $obj->status = $code;
        if ($reasonPhrase == '' && isset(static::$httpCodes[$code])) {
            $this->reasonPhrase = static::$httpCodes[$code];
        } else {
            $this->reasonPhrase = (string)$reasonPhrase;
        }
        return $obj;
    }

    /**
     * 返回响应状态码的原因短语
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * 返回加上cookie的新对象
     *
     * @param Cookie $cookie
     * @return static
     */
    public function withCookie(Cookie $cookie)
    {
        $obj = clone $this;
        $obj->headers->add('Set-Cookie', $cookie->toHeader());
        return $obj;
    }

    /**
     * 返回输出内容长度
     *
     * @return int|null
     */
    public function getContentLength()
    {
        return $this->getBody()->getSize();
    }

    public function __toString()
    {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= Response::EOL;
        foreach ($this->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . Response::EOL;
        }
        $output .= Response::EOL;
        $output .= (string)$this->getBody();

        return $output;
    }
}