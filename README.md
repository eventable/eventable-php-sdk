## Eventable PHP SDK

[Official API Documentation](http://docs.eventable.apiary.io/)

### Authorization
To authorize your application with Eventable you need to first send a cURL request to retrieve your OAuth2 token. You should contact your account manager to obtain the `client_id`.

```curl
curl -X POST \
    -H "Content-Type: application/json" \
    -d '{"grant_type":"password","username":"USERNAME","password":"PASSWORD","client_id":"<client_id>"}' \
    https://api.eventable.com/v1/token/
```
You'll receive a response like this:
```json
{"access_token": "YKbyOxWyxplYQhG6eFGoEaySxnN0jW", "token_type": "Bearer", "expires_in": 315360000, "refresh_token": "NzY8dDEaBibiXNOAQtMzb96Fb3k0xR", "scope": "read write"}
```

Once you've received your token, you can pass it to the Eventable constructor.
```php
$eventableAPI = new Eventable\Eventable("YKbyOxWyxplYQhG6eFGoEaySxnN0jW");
```

Now you have an Eventable instance, and can use its methods to interact with the Eventable API. 

### Events

To create an event, pass an array of event data to the `create_event()` method.
```php
$event_data = array(
    'title'=>'PHP SDK Info Session',
    "description" =>"Come learn about Eventable's PHP SDK!",
    "start_time"=>"2017-05-03T13:00",
    "end_time"=>"2017-05-03T13:30",
    "external_id"=>"REFRESH2"
);

$event = $eventableAPI->create_event($event_data);
```
To see what kinds of data you can pass to the `create_event()` method, see our [API documentation](http://docs.eventable.apiary.io/#reference/events).

Similarly, to update an event, you pass an array of event data to the `update_event()` function. This event data _MUST_ include either an Eventable `id` or an `external_id`. 

```php
$event_data = array(
    'id'=>'58a1efb2b2afda30ca7f19f2',
    'title'=>'PHP SDK Info Session',
    "description" =>"Come learn about Eventable's PHP SDK!",
    "start_time"=>"2017-05-03T13:00",
    "end_time"=>"2017-05-03T14:00"
);

$event = $eventableAPI->update_event($event_data);
```

Deleting an event works the same as updating an event. You pass an array of event data to `delete_event()` that _MUST_ include either an Eventable `id` or an `external_id`.

```php
$event_data = array(
    'id'=>'58a1efb2b2afda30ca7f19f2',
    'title'=>'PHP SDK Info Session',
    "description" =>"Come learn about Eventable's PHP SDK!",
    "start_time"=>"2017-05-03T13:00",
    "end_time"=>"2017-05-03T14:00"
);

$event = $eventableAPI->delete_event($event_data);
```

### Subscribers

Creating a subscriber is easy, you just need to pass an alias to the API like this
```php
$subscriber = $e->get_or_create_subscriber_by_alias('will@example.com');
```

You can also simply add subscribers to existing events by alias, which will create a new subscriber if they don't already exist.
```php
$event_data = array(
    'id'=>'58a1efb2b2afda30ca7f19f2',
    'title'=>'PHP SDK Info Session',
    "description" =>"Come learn about Eventable's PHP SDK!",
    "start_time"=>"2017-05-03T13:00",
    "end_time"=>"2017-05-03T14:00"
);

$event = $eventableAPI->create_event($event_data);
$was_added = $eventableAPI->add_event_to_subscriber_by_alias($event_data, 'will@example.com)
```

Similarly, you can also remove subscribers from events by subscriber alias. In this case, the event and subscriber must both already exist _and_ the event must have been previously added to the subscriber.
```php
$event_data = array(
    'id'=>'58a1efb2b2afda30ca7f19f2',
    'title'=>'PHP SDK Info Session',
    "description" =>"Come learn about Eventable's PHP SDK!",
    "start_time"=>"2017-05-03T13:00",
    "end_time"=>"2017-05-03T14:00"
);

$was_removed = $eventableAPI->remove_event_from_subscriber_by_alias($event_data, 'will@example.com)
```
