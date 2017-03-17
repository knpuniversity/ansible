# Ansible Introduction

Welcome to our tutorial about Ansible. Boy, is this stuff fun. Ansible: it's like commanding an army of robots, little robots that are able to go out to servers and do all kinds of crazy stuff like set up servers, deploy code, even boot new servers on Amazon EC2 if you want.

We're going to do all of that cool stuff in this tutorial, step by step, and make you incredibly powerful. Gone are the days of SSH-ing onto a server and simply running command, because Ansible is a much better way to do things.

Now to be the best robot leader you can, download the code from this code to code along with me. When you unzip it, you'll find a 'Start' directory which should have the same code that you see here. Follow the 'Read Me' file to get the project set up. The last step will be to go into your terminal, move into the directory, and run php bin/console server:run to get the project set up. Local host 8000.

Pull that up in your browser, you see Knp University's new side project, MooTube. Bovine fitness. Yep. If the tutorial business falls through, don't worry. We've got a side plan. Subscription-based service to cows to stay in shape.

Of course, our problem now is that we need to actually- The problem now is two things. First, since we have so many developers working on this, we need a way to easily boot up new development servers, and we would also like to deploy this onto Amazon. That's what we're going to use.

This is a Symphony application, and if you're not used to Symphony, that's fine. The really important things are that this is actually a simple project, but it does have a couple of complicated things in it. It actually has a database connection, which is where all the videos are coming from. It even uses Redis to cache a couple of things. So when we boot up our servers, those are the things that we're going to need to make sure are installed, in addition to things like php.

To get all this going, we are going to use Ansible. Before, we used to SSH into our machines, and then manually run whatever commands we needed to do. In a sense, Ansible does the same thing. Ansible is said to be 'agentless.' That means that you don't need to install something on the server that you want to run the commands on. Instead, Ansible uses SSH to go onto the server and run the commands for you without needing to install anything.

But Ansible is more than that. It's more than just running commands. The Ansible tasks, as they're called, are idempotent, which means that Ansible is more about state. If we tell Ansible to create a directory, it's not that it will create the directory every time, it just will make sure that the directory exists. It will create it if it needs to, but otherwise it won't do anything. Ansible tasks guarantee a specific state.

When you run an Ansible task, it will SSH onto the machine, send some code, execute that code, delete the code, and then receive back some JSON that summarizes what happened. Built on top of this simple system is something that can ultimately do whatever we want.

Before we keep going, we need to install Ansible. Since I'm on a Mac, I've already installed it with brew install ansible. If you're on another system, go check out the Ansible docs and see what the best way is to install it. Once it's installed, do ansible --version to make sure you have at least version 2.0.

All right. Let's start doing cool stuff.

