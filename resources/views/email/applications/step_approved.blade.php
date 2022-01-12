@extends('email.layout')
<p>
    Hi {{$notifiable->first_name}},

    Congratulations! Step {{$approvedStep}} of your ClinGen expert panel application has been approved.
</p>
