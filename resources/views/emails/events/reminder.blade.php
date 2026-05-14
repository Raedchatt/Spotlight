<x-mail::message>
# Friendly Reminder: Your Event is Tomorrow!

Hi **{{ $userName }}**,

Just a quick reminder that **{{ $eventName }}** is happening in exactly 24 hours!

### Event Details:
- **Date & Time:** {{ $eventDate }}
- **Location:** {{ $eventLocation }}
- **Tickets Reserved:** {{ $ticketCount }}x ({{ ucfirst($ticketType) }})

We can't wait to see you there. Make sure to have your tickets ready!

<x-mail::button :url="$eventUrl" color="primary">
View Event Details
</x-mail::button>

If you have any questions or need to cancel, please visit your Spotlight dashboard.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
