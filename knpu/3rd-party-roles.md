# 3rd Party Roles

Now the one last really cool thing about roles, is that there's a lot of third party roles that you can actually download into your project to get stuff done. Right now if we refresh our page in that environment ... you'll see that it's taking two seconds to load this page. And the reason it's taking two seconds, is because our project ... instead of default controller, uses Redis. So in a nutshell, this goes out and looks for a couple keys called Total Video Uploads, Total Video Uploads Count, etc. We don't have Redis installed on the server right now, so this is actually failing ... and when it fails, we are just inventing those numbers, if you file this count, Total Video Uploads that's down here, and we're adding a sleep.

So in other words, we've made the site so if you don't have Redis, it doesn't fail, but it does take a really long time to simulate how it would be if Redis wasn't there. It's a long way of saying it's time for us to install Redis on our server.

Now we already have all the skills to do that, but one of the things I always like to do is see if somebody else has already done the work for me. So, I'm going to search for Redis Ansible role, and we're going to find a role called the David Wittman Ansible Redis. And it has a decent number of stars. It looks like it's fairly reputable, it's active, so that's good. So in a nutshell, this role looks just like ours. It has templates, it has vars, it has handlers, it has a few other things like defaults.

So, if we could download this into our project, we could just call this role. And the way you do that is by using a command called Ansible-galaxy, which I'll copy from the command line. Then I move over here ... and we'll run Ansible-galaxy install DavidWittman.Redis. In my case, that's already installed.

By default, Ansible-galaxy installs these tasks, roles globally to your machine, and when you tell Ansible to load a role, it looks not only in your local directory for your roles, it also looks in that global spot on your file system for roles. Now if you want to, instead of installing things globally ... you can add --help to Ansible-galaxy command ... you can also pass a -P option ... and tell it to download the role directly into your project. Which sometimes is better, because then you can have the role directly in your project, though then you need to commit it to your project.

Once you have it installed, whichever way you choose to have ... we'll activate it in basically the same way. Down here ... instead of saying Engine X, we'll now use the DavidWittman.Redis role. And I actually use a slightly longer tap thing here. I'll say role: DavidWittman.Redis, and when you try this, you'll notice that that role needs to have become true. We didn't have to have that with the Engine X role, because it's task add the become true on their own.

So let's go back ... and run our entire playbook in the DEV environment. And while we're waiting for that to finish, I'm going to flip back over to documentation. And without talking too much about roles, what you're going to see here is that there are vars that you can set. Like Redis Bind. Obviously with roles, you're going to need to configure their behavior. The way that's done is internally that role uses variables, and then we have the ability to override those variables when we call ... the role.

Now the only downside to external roles, is that they can add a lot of bloat to your playbook. You'll see here, there's actually running a lot of tasks, and those tasks are taking a decent amount of time. The reason it's doing this, is roles are usually built to run on different machines. So, it's doing a number of tasks here to figure out which version of Ubuntu are we running? What types of utilities do we have available? So that it knows how to properly configure our system. So it makes the role really, really flexible, because it will work on most systems. But, it also adds extra overhead, because it's going to run those every single time. And a good role will try to skip as many things as it can, but it still takes some time.

In this case, the first time we run it, it's going to take a lot of time, because it actually needs to install and compile Redis.

...

And then it finally gets into our stuff.

...

Beautiful. So let's flip back, to our MooTube at DEV environment. Refresh ... and on the second refresh, it takes only 29 milliseconds. Because it's actually loading from Redis. That works because our application is configured already, to look at the local server for Redis. So as soon as it was installed, our application just picked it up.

