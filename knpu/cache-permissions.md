# Cache Permissions

Our app is a *big* 500 error because Symfony can't write to its cache directory.

This is an easy fix... well mostly. Let's start with the easy part: if we 777 the
`var/` directory, we should be good.

Add a new task at the end: "Fix var directory permissions". To refer to the `var/`
directory, I'll create another variable: `symfony_var_dir` set to `{{ symfony_root_dir }}/var`.
Back at the bottom, use the `file` module, set the `path` to the new variable,
and state to `directory`. That'll create the directory if it doesn't exist, but,
it should. Then, they key part: `mode: 0777` and `recurse: yes`.

***TIP
If you're going to 777 your `var/` directory, make sure that you've uncommented
the `umask` calls in `app_dev.php` and `bin/console`. You can see this in the
finished code download.
***

Ok, run the playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

By the way, needing to re-run the *entire* playbook after a tiny change is annoying!
We'll learn a trick in a minute to help with this.

Done! Ok, switch back to your browser and try it. Woohoo! A working Symfony project...
not *our* project yet, but still, winning! We'll use our *real* project next.

## Fixing Permissions... in a more Secure Way?

Setting the directory permissions to 777 is easy... and perfectly fine for a development
machine. But if this were a *production* machine, well, 777 isn't ideal... though
honestly, a *lot* of people do this.

What's better? In a few minutes, we'll add a task to clear and warm up Symfony's
cache. On a production machine, after you've done that, you can set the `var/cache`
permissions *back* to be non-writeable, so 555. In theory, that should just work!
But in practice, you'll probably need to tweak a few other settings to use non-filesystem
cache - like making annotations cache in APC. 

But, that's more about deployment - which we'll save for a different course!
