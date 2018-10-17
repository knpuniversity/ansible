# Extensions, php.ini & lineinfile

PHP, MySQL and Nginx: check, check and check! But we've *only* installed `php7.1-cli`.
An in reality, we need a lot more than that! What about `php7.1-mysql` or `php7.1-fpm`?
Yep, we need those friendly extensions... and a few others.

## Looping: with_items

Wonderfully, on Ubuntu, these are all installed via `apt-get`. We *could* copy and
paste the `php7.1-cli` task over and over and over again for each package. Or,
to level-up our Ansible-awesomeness, we can loop!

Let's see how: change the task's name to `Install PHP packages`:

[[[ code('9ce6f7bc6f') ]]]

Then, instead of `php7.1-cli`, add the very cryptic `"{{ item }}"`:

[[[ code('d9a337303b') ]]]

Finish it with a new `with_items` key *after* the `apt` module. This gets a big
list of the stuff we want: `php7.1-cli`, `php7.1-curl`, ice cream, `php7.1 -fpm`,
`php7.1-intl`, a pony and `php7.1-mysql`:

[[[ code('ca46ddefce') ]]]

***TIP
Using a loop in `apt` module is deprecated and will be removed in version 2.11.
Instead of using the loop and specifying `name: {{ item }}`, you can pass an array
to the `name` key and specify the items like this:

```yaml
---
- hosts: vb

  tasks:
    # ...
    - name: Install PHP packages
      become: true
      apt:
        name:
          - php7.1-cli
          - php7.1-curl
          - php7.1-fpm
          - php7.1-intl
          - php7.1-mysql
        state: latest
```
***

If we need more goodies later, we can add them. Flip over to your terminal and try it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

## Using Jinja

While we wait, let's check out the code we just wrote. For the *first* time, we're
seeing Ansible's templating language in action! Yep, whenever you see `{{ }}`, you're
writing Jinja code - a Python templating language... which - guess what - is
more or less identical to Twig. Win!

In this case, we're opening up Jinja to print a variable called `item`. That works
because `Ansible` has this nice `with_items` loop feature. And notice, this is *not*
special to the `apt` module - it'll work anywhere.

Oh, and those quotes *are* important:

[[[ code('7e67e8cb3c') ]]]

Quoting is usually optional in YAML. But if a value starts with `{{`, it's mandatory.

Head back to the terminal. Yes! Celebrate! PHP extensions installed! I'll move to
my third tab where I've already run `vagrant ssh` to get into the VM. Check for the
MySQL extension:

```terminal
php -i | grep mysql
```

See that PDO MySQL stuff? That proves it worked!

Re-run that command again and look for `timezone`:

```terminal
php -i | grep timezone
```

## Tweaking `php.ini` settings with lineinfile

Hmm, it says `date.timezone` no value, which means that it is *not* set in `php.ini`.
Since PHP 7.0, that's not a *huge* deal - in PHP 5 this caused an annoying warning.
But, I still want to make sure it's set.

Question number 1 is... where the heck is my `php.ini` file? Answer, run:

```terminal
php --ini
```

There it is `/etc/php/7.1/cli/php.ini`. Open that up in `vim` and hit `/timezone`,
enter, to find that setting:

```ini
[Date]
; Defines the default timezone used by the date functions
; http://php.net/date.timezone
;date.timezone =
```

Ok, it's commented-out right now. We want Ansible to uncomment that line and set it
to UTC. Quit with Escape, `:q`, enter.

So how can we tell Ansible to make a change *right* in the middle of a file? Of course,
Ansible has a module *just* for that! Search for the "Ansible lineinfile" module.
Ah, ha!

> Ensure a particular line is in a file, or replace an existing line

Let's check out the options! The only required one is `path` - the file we need
to change. Then, we can use the `regexp` option to find the target line and `line`
as the value to replace it with.

Before we do this, look back at the `path` option. It says that *before* Ansible
2.3, this was called `dest`, `destfile` or `name` instead of `path`. What version
do we have? Find out:

```terminal
ansible --version
```

We're on 2.1! So instead of `path`, we need to use `dest`. This is something to
watch out for... because at the time of this recording, Ansible 2.3 isn't even
released yet! For some reason, Ansible always shows the docs for its latest, unreleased
version.

Let's rock! Add the new task: `Set date.timezone for CLI`. Add `become: true` and
use the `lineinfile` module:

[[[ code('fc78c9ba94') ]]]

For options, pass it `dest: /etc/php/7.1/cli/php.ini` and `regexp: date.timezone =`:

[[[ code('75b5d3f036') ]]]

We're not leveraging any regex here: this will simply find a line that contains `date.timezone =`.
Finally, add `line: date.timezone = UTC`:

[[[ code('a8a99361fc') ]]]

With the `line` option, the *entire* line will be replaced - not just the part that
was matched from the `regexp` option. That means the comment at the beginning of the
line *will* be removed.

***TIP
There is also an `ini_file` module, which makes modifying `.ini` files even easier.
For an example, see: http://bit.ly/knpu-ini-module
***

Now, copy that entire task and paste it. In Ubuntu, there are 2 different `php.ini`
files: rename this one to `Set date.timezone for FPM`. Change the `dest` path
from `cli/` to `fpm/`. That's the correct path inside the VM:

[[[ code('25bc8a3e94') ]]]

Run it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Before that finishes, flip back to the VM and check the timezone setting:

```terminal
php -i | grep timezone
```

No value. Then... once it finishes... try it again:

```terminal
php -i | grep timezone
```

Got it! UTC! I'll open up my `php.ini` to be sure... and...

```ini
[Date]
; Defines the default timezone used by the date functions
; http://php.net/date.timezone
date.timezone = UTC
```

Yes! The line was perfectly replaced.

Say hello to `lineinfile`: your Swiss army knife for updating configuration files.
