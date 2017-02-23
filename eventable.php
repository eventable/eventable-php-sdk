<?php

namespace Eventable;

define(EVENTABLE_API_URL, 'http://localhost:8050/v1');

class Eventable
{

    function __construct($token) {
        $this->client = new EventableClient($token);
    }

    public function create_event(array $event_data) {
        $endpoint = '/events/';

        $response = $this->client->post_request($endpoint, $event_data);
        if ($response['status'] == 400) {
            throw new EventableSDKException($response['content']);
        }
        $event = json_decode($response["content"]);

        return $event;
    }

    public function update_event(array $event_data) {
        $endpoint = Null;

        if (isset($event_data['id'])) {
            $endpoint = '/events/' . $event_data['id'] . '/';
        }
        elseif (isset($event_data['external_id'])) {
            $get_endpoint = '/events/?external_id=' . $event_data['external_id'];
            $get_response = $this->client->get_request($get_endpoint);
            $content = json_decode($get_response["content"]);
            if ($content->total < 1) {
                throw new EventableSDKException("no event with the given external_id exists");
            }
            $event = $content->results[0];
            $endpoint = '/events/' . $event->id . '/';
        }
        else {
            throw new EventableSDKException('need an event id or external event id');
        }
        $response = $this->client->put_request($endpoint, $event_data);
        if ($response['status'] != 200) {
            throw new EventableSDKException($response['content']);
        }
        $event = json_decode($response["content"]);

        return $event;
    }

    public function delete_event(array $event_data) {
        $endpoint = Null;

        if (isset($event_data['id'])) {
            $endpoint = '/events/' . $event_data['id'];
        }
        elseif (isset($event_data['external_id'])){
            $get_endpoint = '/events/?external_id=' . $event_data['external_id'];
            $get_response = $this->client->get_request($get_endpoint);
            $content = json_decode($get_response["content"]);
            if ($content->total < 1) {
                throw new EventableSDKException("no event with the given external_id exists");
            }
            $event = json_decode($get_response->content);
            $endpoint = '/events/' . $event['id'] . '/';
        }
        else {
            throw new EventableSDKException('need an event id or external event id');
        }

        $response = $this->client->delete_request($endpoint, $event_data);

        if ($response['status'] != 204) {
            throw new EventableSDKException($response['content']);
        }

        return true;
    }

    public function get_or_create_subscriber_by_alias($alias) {
        $endpoint = '/subscribers/?alias=' . $alias;

        $response = $this->client->get_request($endpoint);
        $content = json_decode($response["content"]);

        if ($content->total < 1) {
            $post_endpoint = '/subscribers/';
            $post_data = array('alias' => $alias);
            $response = $this->client->post_request($post_endpoint, $post_data);
            $content = json_decode($response["content"]);
            if ($response['status'] != 201) {
                throw new EventableSDKException($response['content']);
            }
            $subscriber = $content->results[0];
        }
        elseif ($content->total >= 1){
            $subscriber = $content->results[0];
        }
        else {
            throw new EventableSDKException('there was an error creating/updating your subscriber');
        }

        return $subscriber;
    }

    public function add_event_to_subscriber_by_alias(array $event_data, $alias) {
        $subscriber = $this->get_or_create_subscriber_by_alias($alias);
        $endpoint = '/subscribers/' . $subscriber->id . '/custom_events/';

        if (isset($event_data['id'])) {
            $event_id = $event_data['id'];
        }
        elseif (isset($event_data['external_id'])){
            $get_endpoint = '/events/?external_id=' . $event_data['external_id'];
            $response = $this->client->get_request($get_endpoint);
            $content = json_decode($response["content"]);
            if ($content->total < 1) {
                throw new EventableSDKException("no event with the given external_id exists");
            }
            $event = $content->results[0];
            $event_id = $event->id;
        }
        else {
            throw new EventableSDKException('need an event id or external event id');
        }

        $response = $this->client->post_request($endpoint, array('id' => $event_id));

        if ($response["status"] == 400) {
            $content = json_decode($response['content']);
            echo var_dump($content);
            if ($content->detail == "Event already exists.") {
                return true; # event was already added, but it's still good to return true
            }
        }

        return ($response["status"] == 201); # return true if successfully added
    }

    public function remove_event_from_subscriber_by_alias(array $event_data, $alias){
        $subscriber = $this->get_or_create_subscriber_by_alias($alias);

        if (isset($event_data['id'])) {
            $event_id = $event_data['id'];
        }
        elseif (isset($event_data['external_id'])){
            $get_endpoint = '/events/?external_id=' . $event_data['external_id'];
            $response = $this->client->get_request($get_endpoint);
            $content = json_decode($response["content"]);
            if ($content->total < 1) {
                throw new EventableSDKException("no event with the given external_id exists");
            }
            $event = $content->results[0];
            $event_id = $event->id;
        }
        else {
            throw new EventableSDKException('need an event id or external event id');
        }

        $endpoint = '/subscribers/' . $subscriber->id . '/custom_events/' . $event_id . '/';
        $response = $this->client->delete_request($endpoint);

        if ($response['status'] != 204){
            throw new EventableSDKException($response['content']);
        }

        return true;
    }

}


class EventableClient
{

    function __construct($token) {
        $this->token = $token;
    }

    private function create_request($url, $method) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token
        ));

        return $ch;
    }

    public function get_request($endpoint, array $params=array()) {
        $url = EVENTABLE_API_URL . $endpoint;
        $ch = $this->create_request($url, "GET");
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array('content' => $response, 'status' => $httpCode);
    }

    public function post_request($endpoint, array $data) {
        $url = EVENTABLE_API_URL . $endpoint;
        $ch = $this->create_request($url, "POST");
        $body = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array('content' => $response, 'status' => $httpCode);
    }

    public function put_request($endpoint, array $data) {
        $url = EVENTABLE_API_URL . $endpoint;
        $ch = $this->create_request($url, "PUT");
        $body = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array('content' => $response, 'status' => $httpCode);
    }

    public function delete_request($endpoint) {
        $url = EVENTABLE_API_URL . $endpoint;
        $ch = $this->create_request($url, "DELETE");
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array('content' => $response, 'status' => $httpCode);
    }

}

class EventableSDKException extends \Exception
{
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

?>
