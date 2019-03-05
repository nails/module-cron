# Cron Events
> Documentation is a WIP.


This module exposes the following events through the [Nails Events Service](https://github.com/nails/common/blob/master/docs/intro/events.md) in the `nails/module-cron` namespace.

> Remember you can see all events available to the application using `nails events`



- [Nails\Cron\Events::CRON_STARTUP](#cron-startup)
- [Nails\Cron\Events::CRON_READY](#cron-ready)



<a name="cron-startup"></a>
### `Nails\Cron\Events::CRON_STARTUP`

Fired when the Cron module starts

**Receives:**

> ```
> none
> ```


<a name="cron-ready"></a>
### `Nails\Cron\Events::CRON_READY`

Fired when the Cron module is ready but before the controller is executed

**Receives:**

> ```
> none
> ```
