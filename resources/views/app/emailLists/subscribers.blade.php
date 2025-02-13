@extends('mailcoach::app.emailLists.layouts.edit', ['emailList' => $emailList])

@section('breadcrumbs')
    <li><span class="breadcrumb">{{ $emailList->name }}</span></li>
@endsection

@section('emailList')
    <div class="table-actions">
        <div class=buttons>
            <button class="button" data-modal-trigger="create-subscriber">
                <x-icon-label icon="fa-user" :text="__('Add subscriber')"/>
            </button>

            <x-modal :title="__('Create subscriber')" name="create-subscriber" :open="$errors->any()">
                @include('mailcoach::app.emailLists.subscriber.partials.create')
            </x-modal>

            <div class="dropdown" data-dropdown>
                <button class="button" data-dropdown-trigger>
                    <span class="icon-button">
                        <i class="fas fa-ellipsis-v | dropdown-trigger-rotate"></i>
                    </span>
                </button>
                <ul class="dropdown-list dropdown-list-right | hidden" data-dropdown-list>
                    <li>
                        <a href="{{route('mailcoach.emailLists.import-subscribers', $emailList)}}">
                            <x-icon-label icon="fa-cloud-upload-alt" :text="__('Import subscribers')"/>
                        </a>
                    </li>
                    @if($subscribers->count() > 0)
                        <li>
                            <x-form-button
                                :action="route('mailcoach.emailLists.subscribers.export', $emailList) . '?' . request()->getQueryString()">

                                @if($emailList->allSubscribers()->count() === $subscribers->total())
                                    <x-icon-label icon="fa-file" :text="__('Export all subscribers')"/>
                                @else
                                    <x-icon-label icon="fa-file" :text="__('Export :total :subscriber', ['total' => $subscribers->total(), 'subscriber' => trans_choice(__('subscriber|subscribers'), $subscribers->total())])"/>
                                @endif
                            </x-form-button>
                        </li>
                        <li>
                            <x-form-button
                                :action="route('mailcoach.emailLists.destroy-unsubscribes', $emailList)"
                                method="DELETE" data-confirm="true" :data-confirm-text="__('Are you sure you want to delete unsubscribes in :emailList?', ['emailList' => $emailList->name])">
                                <x-icon-label icon="fa-trash-alt" :text="__('Delete unsubscribes')" :caution="true"/>
                            </x-form-button>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        @if($emailList->allSubscribers()->count())
            <div class="table-filters">
                <x-filters>
                    <x-filter :queryString="$queryString" attribute="status" active-on="">
                        {{ __('All') }}
                        <x-counter :number="$emailList->allSubscribers()->count()"/>
                    </x-filter>
                    <x-filter :queryString="$queryString" attribute="status" active-on="unconfirmed">
                        {{ __('Unconfirmed') }}
                        <x-counter :number="$emailList->allSubscribers()->unconfirmed()->count()"/>
                    </x-filter>
                    <x-filter :queryString="$queryString" attribute="status" active-on="subscribed">
                        {{ __('Subscribed') }}
                        <x-counter :number="$emailList->allSubscribers()->subscribed()->count()"/>
                    </x-filter>
                    <x-filter :queryString="$queryString" attribute="status" active-on="unsubscribed">
                        {{ __('Unsubscribed') }}
                        <x-counter :number="$emailList->allSubscribers()->unsubscribed()->count()"/>
                    </x-filter>
                </x-filters>
                <x-search :placeholder="__('Filter subscribers…')"/>
            </div>
        @endif
    </div>

    @if($emailList->allSubscribers()->count())
        <table class="table table-fixed">
            <thead>
            <tr>
                <th class="w-4"></th>
                <x-th sort-by="email">{{ __('Email') }}</x-th>
                <th class="hidden | md:table-cell">{{ __('Tags') }}</th>
                @if(request()->input('filter.status') === \Spatie\Mailcoach\Enums\SubscriptionStatus::UNSUBSCRIBED)
                    <x-th sort-by="-unsubscribed_at" class="w-48 th-numeric hidden | md:table-cell">{{ __('Unsubscribed at') }}</x-th>
                @else
                    <x-th sort-by="-created_at" class="w-48 th-numeric hidden | md:table-cell">{{ __('Subscribed at') }}</x-th>
                @endif

                <th class="w-12"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($subscribers as $subscriber)
                <tr>
                    <td>
                        @if ($subscriber->isUnconfirmed())
                            <i class="fas fa-question-circle text-orange-500" title="{{ __('Unconfirmed') }}"></i>
                        @endif
                        @if ($subscriber->isSubscribed())
                            <i class="fas fa-check text-green-500" title="{{ __('Subscribed') }}"></i>
                        @endif
                        @if ($subscriber->isUnsubscribed())
                            <i class="fas fa-ban text-gray-400" title="{{ __('Unsubscribed') }}"></i>
                        @endif
                    </td>
                    <td>
                        <a class="break-words"
                           href="{{ route('mailcoach.emailLists.subscriber.details', [$subscriber->emailList, $subscriber]) }}">
                            {{ $subscriber->email }}
                        </a>
                        <div class="td-secondary-line">
                            {{ $subscriber->first_name }} {{ $subscriber->last_name }}
                        </div>
                    </td>
                    <td class="hidden | md:table-cell">
                        @foreach($subscriber->tags()->pluck('name') as $tag)
                            <span class=tag>{{ $tag }}</span>
                        @endforeach
                    </td>
                    <td class="td-numeric hidden | md:table-cell">{{
    $subscriber->isUnsubscribed()
    ? $subscriber->unsubscribed_at->toMailcoachFormat()
    : $subscriber->created_at->toMailcoachFormat() }}</td>
                    <td class="td-action">
                        <div class="dropdown" data-dropdown>
                            <button class="icon-button" data-dropdown-trigger>
                                <i class="fas fa-ellipsis-v | dropdown-trigger-rotate"></i>
                            </button>
                            <ul class="dropdown-list dropdown-list-left | hidden" data-dropdown-list>
                                @if ($subscriber->isUnconfirmed())
                                    <li>
                                        <x-form-button
                                            :action="route('mailcoach.subscriber.resend-confirmation-mail', [$subscriber])"
                                            method="POST" data-confirm="true" :data-confirm-text="__('Are you sure you want to resend the confirmation mail :email?', ['email' => $subscriber->email])">
                                            <x-icon-label icon="fa-envelope" :text="__('Resend confirmation mail')"/>
                                        </x-form-button>
                                    </li>
                                    <li>
                                        <x-form-button
                                            :action="route('mailcoach.subscriber.confirm', [$subscriber])"
                                            method="POST" data-confirm="true" :data-confirm-text="__('Are you sure you want to confirm :email?', ['email' => $subscriber->email])">
                                            <x-icon-label icon="fa-check" :text="__('Confirm')"/>
                                        </x-form-button>
                                    </li>
                                @endif
                                @if ($subscriber->isSubscribed())
                                    <li>
                                        <x-form-button
                                            :action="route('mailcoach.subscriber.unsubscribe', [$subscriber])"
                                            method="POST" data-confirm="true" :data-confirm-text="__('Are you sure you want to unsubscribe :email?', ['email' => $subscriber->email])">
                                            <x-icon-label icon="fa-ban" :text="__('Unsubscribe')"/>
                                        </x-form-button>
                                    </li>
                                @endif
                                @if ($subscriber->isUnsubscribed())
                                    <li>
                                        <x-form-button
                                            :action="route('mailcoach.subscriber.resubscribe', [$subscriber])"
                                            method="POST" data-confirm="true" :data-confirm-text="__('Are you sure you want to resubscribe :email?', ['email' => $subscriber->email])">
                                            <x-icon-label icon="fa-redo" :text="__('Resubscribe')"/>
                                        </x-form-button>
                                    </li>
                                @endif
                                <li>
                                    <x-form-button
                                        :action="route('mailcoach.emailLists.subscriber.delete', [$subscriber->emailList, $subscriber])"
                                        method="DELETE" data-confirm="true" :data-confirm-text="__('Are you sure you want to delete subscriber :email?', ['email' => $subscriber->email])">
                                        <x-icon-label icon="fa-trash-alt" :text="__('Delete')" :caution="true"/>
                                    </x-form-button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <x-table-status :name="__('subscriber|subscribers')" :paginator="$subscribers" :total-count="$totalSubscriptionsCount"
                        :show-all-url="route('mailcoach.emailLists.subscribers', $emailList)">
        </x-table-status>
    @else
        <p class="alert alert-info">
            {{ __('So where is everyone? This list is empty.') }}
        </p>
    @endif
@endsection
