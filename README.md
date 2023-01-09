## reCAPTCHA v3 - wordpress implementation

How to add user-friendly captcha to wordpress project? Solution: after ajax form submit - connect to recaptcha siteverify service and get SPAM score.

### Demo
- We have custom PHP template with ajax form
- On form submit - admin-ajax is called
- We get SPAM score from Google Recaptcha version 3
- Score is value between 0 and 1. 1.0 is very likely a good interaction, 0.0 is very likely a bot.
- Having this score - we can decide what todo: save data to database or display error
- Form is saving user rating as custom-field

### Configuration
- register new website in https://www.google.com/recaptcha/about/ - reCAPTCHA version 3
- add website domains to the settings ( https://www.google.com/recaptcha/admin/site/XXXXXXX/settings )
- add to wp-config your recaptcha keys:
```
define( 'CT_RECAPTCHA_PUBLIC', 'XXX' );
define( 'CT_RECAPTCHA_SECRET', 'YYY' );'
```
- create new wordpress page 'Ajax form' and set PHP template (Ajax Form)
- view page, select post and submit form (Save user rating)

### Adjust settings
- functions.php , find:
```
  $g_recaptcha_allowable_score = 0.3;
```
and adjust value (what is your threshold for accepting risk ratings). Value that is closer to 0 - is more likely to be a BOT )

### Troubleshooting
#### I'm getting captcha error: incorrect-captcha-sol
- Make sure that your site domain is added to recaptcha configuration. If you're working locally - add localhost to the list.