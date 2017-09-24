<?php

declare(strict_types=1);

namespace GraphQl\Client;

abstract class GraphQlService
{
    /**
     * @var string
     */
    private $url;

    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    protected function send(string $query)
    {
        $body = ['query' => $query];

        $encodedBody = json_encode($body);

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($encodedBody)
        ]);

        $result = json_decode(curl_exec($ch), true);

        if (array_key_exists('errors', $result)) {
            // @TODO handle some errors, yo
            throw new \Exception('Shiz hit the fan');
        }

        return $result['data'];
    }
}
