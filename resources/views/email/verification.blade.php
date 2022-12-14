@component('mail::message')

   {{__('Votre code vérification pour la validation de votre mail est') }} :
   <p class="code">{{$code}}</p>

@endcomponent
