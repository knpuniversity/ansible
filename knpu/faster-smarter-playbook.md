# Faster Smarter Playbook

If you run your playbook twice in a row now, you'll see "changed 2". And the reason we're seeing that is because, on every single playbook run, we're downloading Composer ... And then moving it globally.

It's not a big deal, but that's pretty wasteful. After all, our system already has Composer, and this is probably just downloading the same version over and over and over again. So this is where we can make our playbook really smart. We can start to do things conditionally, based on facts about the host system. So, for example, if the Composer executable already exists at /user/local/bin/composer, there's no reason for us to download it again.

To figure that out, we're going to use a really handy module called "stat". Which is similar to the Linux "stat" command, if you've used that before. It basically gives you lots of information about a file, including whether or not that file exists. Pretty simple to use the stat module, you pass it the path that you want to look at.

So before we download Composer, let's add a new task called "Check for Composer". We'll set the path to /usr/local/bin/composer. And then we're going to register a new variable called "composer_stat". Make sure you add tags to deploy it, like the other Composer commands.

Now we don't know what that composer_stat variable looks like. We know it's going to have information about that file. So let's add a debug task, temporarily, with "var" set to composer_stat. And also add tags deploying that so we can just run that tag right now.

[inaudible 00:02:05 All right?] , flip over to your playbook and run it with "-t deploy" at the end. And there it is. As soon as you see it, CTRL+C out to end it, because that's what we want.

Awesome! So it looks like composer_stat has a "stat" key with a bunch of information about that file. The most important one for us is "exists". This says that that file does exist.

And that's really powerful. I'll remove the debug task. And our goal is to run these next three tasks if that file does not exist. So we can add our "when" and say "not composer_stat.stat.exists". In other words, check this stat.exists key here, and if that is true, we want to not run this.

So I'll copy and one command and we'll put it down below our "Move Composer globally", and our "Set Permissions on Composer". All right, let's try that. We'll re-run the playbook again the "deploy" tag.

And we're looking to see that those three tasks are skipped this time. And there they are! And I'll CTRL+C out as soon as we get there. Perfect. So the only issue now is, eventually, our Composer executable will become very out-of-date. So we're going to need to make sure that it's up to the latest version.

So, below the set permissions, let's set a new task called "Make sure Composer is at its latest version". This time I'll actually use the composer module. We'll set "working_dir" just like we did before to our "symfony/root/dir". Though it won't matter in this case because, ultimately, what we're going to run here is self-update. The command that you use in Composer to download a new version of itself.

And I'll even add when "composer_stat.stat.exists". In other words, we won't run this command if Composer was just downloaded. It's not really that important, but it will save a little bit of time on that first we run ansible. And we'll add our tags, "deploy".

Perfect. Let's run that. But this time, when you run it, I want you to add a "-- verbose" option. This is a nice trick that's going to avoid us needing to do so many debug tasks to see variables. Because, as you see, as it runs the tasks, it's actually going to show you the output from those items.

So, as soon as you run and make sure Composer's installed with the latest version, hit CTRL+C. And you can see that, when this ran, it's stdout was "You are already using composer version 1.4.1". The reason I wanted to stop here is, you notice that, even though this didn't do anything, it was marked as "changed". Which is not surprising because we're just running a self-update command. The composer module isn't really set up to know about that command, so we're kind of hacking it a little bit. So we need to make this command a little smarter so that this knows whether or not it's actually been changed.

We've actually done this before. Down near the bottom ... With our database command. So I'm going to copy the changed_when one from the create_db if it not exists, scroll back up, and paste that into our spot. Now we'll need to register a variable so above that I'll say "register" ... We'll say "composer_self_update". And we'll use that variable down in the "change_when". Then all we need to do is search for "You are already using composer version". If that shows up in the command, then we know we aren't changed. So I'll paste that over the search right here.  So, this task is changed when we do not see the message "You are already using composer version".

So let's make sure we got that right. We'll run the task again. And there it is. And I'll stop it. So, it still has the same message, but this time it's okay ... It's not yellow, it's not changed.

So with this "when" key, you can make really really smart playbooks. You can make them as smart as you want, to make them run as fast as you want. So what other things could we do to make our playbook faster. Well, one of the things is ... Sometimes our code doesn't change. So when we checkout the git repository, which happens further up, this is going to say "okay" if the code didn't actually change. But in our case, there were no code changes, so it says "changed" false.

If we know that the code didn't change, then it might not make sense to install our composer dependencies. After all, if the code didn't change, how would the composer dependencies need to change? And, other things, like running the migrations. Again, if the code didn't change, there are no new migrations. Or even clearing the cache. That usually only needs to happen when your code changes.

So under our git clone task, let's register a new variable called "repo_code". And we already know from the output here, that we're looking for the "changed" key on that variable. So what we could do, is use this "repo_code" variable directly instead of repo_code.changed, and use that in a "when" command further below to, for example, not install the composer dependencies.

But I'm going to do something just a little fancier. Which is, below this, I'm going to add a new task called "Register code_changed variable". And we're going to use the "set_fact" module, which we used a little bit earlier inside of our pre_tasks. The set_fact module, is a module used to set a variable. In this case, we're setting a variable called "symfony_env".

This time, I'm going to set a variable called "code_changed", and we're going to set that to repo_code.changed. And the only reason we're doing this, is just to make our other "when" statements a little bit cleaner, because now they can just say "when code_changed" or "when not code_changed" instead of using repo_code.changed.

And we'll add our tag onto that so that that runs the same time all these other deployed tasks run. So now down below on the "Install Composer's Dependencies", we can say "when code_changed". I'll copy that and [inaudible 00:11:00] anywhere else that you think it makes sense. Like "Execute migrations" when code_changed, and clearing the cache when code_changed.

Phew! So now let's rerun our entire playbook again. I'll take of the "-- verbose" with our deploy tag. The three Composer tasks were skipped. We skipped the install composer's dependencies. And we skipped executing the migrations and clearing the cache. And remember, loading the data fixtures was skipped because we're not in a prod environment. So you can see how much faster that ran that time.

All right, next let's talk about organizing our playbook because, as you can see as I scroll up and down, I'm getting a little lost here. Things are getting a little bit disorganized and I want to fix that.



