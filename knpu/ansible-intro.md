# Hello Ansible!

Welcome automation lovers!!! You've come to the right place, because this tutorial
is all about Ansible! And boy, it's just a *lot* of fun. Imagine if we could take
control of an army of tiny, but mighty robots... simply by writing a YAML file. Think
of the servers we could launch! The infrastructure we could build! The code we could
deploy! The laundry we could wash! Well, actually, these Ansible robots are sorta
virtual robots, so yes to automating things like server setup... but probably not
to automating your laundry. However, I *do* hope one of you proves me wrong!

To be the best robot overlord you can be, download the course code from this page
and code along with me! After unzipping the file, you'll find a `start/` directory,
which will have the same code that you see here. Follow the `README.md` file to get
your project up and running. Well actually, setting up the project isn't *that* important,
because we will ultimately use Ansible to do that! But if you want to see things
working now follow that file. The last step will be to go into your terminal, move
into the directory, and run

```terminal
php bin/console server:run
```

to launch the built-in PHP web server. In your browser, open up `http://localhost:8000`.
Introducing, our new side project here at KnpUniversity: MooTube! Yes, far too many
cows are out of shape and need some serious exercise! So in case this whole tutorial
business falls through, we'll turn to bovine fitness: a subscription-based service
to keep cows in tip-top shape.

## The App & Our Mission!

The first version of our app is done actually! But there are two problems. First,
we'd love to have a way to easily boot up development servers complete with PHP, a
web server and anything else we need to get our app working. And second, it would
be *bananas* if we could have an automated way to *deploy* code to Amazon EC2 servers.
Well, that's *exactly* what we're going to do.

Our project is a Symfony application... but if you're not used to Symfony, that's
*no* problem. From a server-perspective, the app requires a few interesting things:

[[[ code('a32773e729') ]]]

First, it needs a database connection to load the video info. And second, it uses
Redis to cache a few things. So when we boot up our servers, that stuff needs to
be there.

## Introducing Ansible

There's a good chance you've setup a server before. I know I've setup a *ton*, and
it's always the same, manual, error-prone, confusing process: SSH into the server,
manually run a bunch of commands, have errors, Google things, run more commands,
and edit a bunch of files.

Ansible... kinda does the same thing. When you execute something with Ansible,
it ultimately SSH's onto the server and runs some commands. Ansible is said to be
"agentless", which just means that you don't need to install anything on the target
server. As long as you can SSH to a server, you can unleash your Ansible robot army
onto it. That's pretty cool.

But Ansible is even more interesting than that. When you execute an Ansible *task* -
that's what they're called - it is *idempotent*... well, usually it is. Idempotency
is an obscure - but cool - word to mean that Ansible tasks don't just dumbly run
a command. What they *really* do is *guarantee* that the server finishes in a specific
*state*. For example, if we tell Ansible to create a directory, it doesn't necessarily
mean that it will run a `mkdir` command. Instead, it means that Ansible will just
make sure that the directory exists - only creating it if necessary.

This idea of guranteeing a "state" - like "this directory must exist" - is much
more powerful than randomly running commands over SSH. These tasks also send back
JSON info about what happened, which we can use to tweak and influence what happens
next.

## Installing Ansible

Ok, let's start playing already! First, we need to install Ansible of course! Since
I'm on a Mac, I've already installed it with: `brew install robotarmy`. I mean,

```terminal
brew install ansible
```

If you're on different system, check out the [Ansible docs][[installation]] for your
install instructions. Unfortunately, if you're using Windows, you *can't* use Ansible.
Well, you can't natively. If you're virtualizing a Linux machine or are using Windows 10
with the Linux subsystem, then you can install Ansible there.

Once you've got it, run

```terminal
ansible --version
```

to make sure you have at least version 2.0.

Ok team, let's boot up our robot army!


[installation]: http://docs.ansible.com/ansible/intro_installation.html#installation
