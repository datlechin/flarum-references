<x-mail::plain.notification>
<x-slot:body>
{!! $translator->trans('datlechin-references.email.post_referenced.body', [
'{username}' => $blueprint->source->user?->display_name ?? '',
'{title}' => $blueprint->post->discussion?->title ?? '',
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->source->discussion_id, 'near' => $blueprint->source->number]),
]) !!}
</x-slot:body>
</x-mail::plain.notification>
