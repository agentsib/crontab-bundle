AgentSIB Crontab bundle
=======================

In development yet

# Configure events

In cronbundle available events since 1.0.2

You can subscribe to crontab success and error events for example in `service.yml` by
```
service_name:
    class: AcmeBundle\EventListeners\Listener
    tags:
        - {name: kernel.event_listener, event: agentsib.crontab.error, method: onError}
```
or
```
service_name:
    class: AcmeBundle\EventListeners\Listener
    tags:
        - {name: kernel.event_listener, event: agentsib.crontab.success, method: onSuccess}
```