@extends('email.layout')

<p>Hi, {{$notifiable->first_name}}</p>

<p>
    The <strong>{{$group->display_name}}</strong> has submitted their step <strong>{{$group->expertPanel->current_step}}</strong> application.  The core group has reviewed the application and it is ready for your review.
</p>

<p>
    To review the application and see the Core Group's comments
    <a href="https://gpm.clinicalgenome.org">log in to the GPM</a>
</p>

Thanks,
<br>
GPM Bot
