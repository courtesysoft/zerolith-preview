# zperm

Zperm is a library for handling user permissions.
It is primarily used in cases where applications are being plugged into each other, use granular permissions, and permission numbers or user types that determine permissions on a per-case basis.
It's also used to ensure that a user has the correct permissions to access parts of zpanel that reveal sensitive information.


### Usage

Before using zperm, you need to send the required data to zperm with:
`zperm::setUser()`

You can also use `zperm::getUser()` to retrieve both the information sent, and/or web-server level details about the current visitor, IE their IP address.

The rest is fairly self-explanatory if you check out the source code in `/zerolith/classes/zperm.php`
