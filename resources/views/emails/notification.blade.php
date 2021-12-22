@component('mail::message')
# Monitolite Notification Report

Hello {{ $report['contact']['firstname'] }},

You will find below the full report digest of the Monitolite monitoring application.

@component('mail::table')
| Host         | Status   | Datetime |
|:------------:|:--------:|:--------:|
@foreach ($report['tasks'] as $t)
@foreach ($t['history'] as $h)
| [{{ $h['task']['host'] }}]({{ $h['task']['host'] }})  | {{ $h['status'] == 1 ? '**UP**': '**DOWN**' }}  |  {{ date('Y-m-d H:i:s', strtotime($h['created_at'])) }} |
@endforeach
@endforeach
@endcomponent

@component('mail::button', ['url' => $url])
View the dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent