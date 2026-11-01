# LearnLingo homepage data API

The theme exposes a read-only, same-origin JSON endpoint for custom home components:

```text
GET /theme/learnlingo/home_api.php?section=all&limit=12&offset=0
```

Use it from JavaScript loaded by a Moodle theme AMD module or another trusted frontend component; HTML entered in the homepage settings does not execute scripts.

```js
const url = M.cfg.wwwroot + '/theme/learnlingo/home_api.php?section=all&limit=12&offset=0';
const response = await fetch(url, {credentials: 'same-origin'});
if (!response.ok) {
    throw new Error('Could not load the homepage data');
}
const data = await response.json();
if (data.loggedin) {
    console.log(data.user.firstname, data.courses.items);
} else {
    console.log(data.publiccourses.items);
}
```

## Sections

Every response includes the Boolean `loggedin`. Pass `section` as `all` (default), `user`, `courses`, `publiccourses`, `cohorts`, or `popular`. Sections return:

- `user`: only the currently authenticated user, including name, email, city, country, institution, department and the user's own custom profile fields. For visitors, the value is `null`. You cannot query a different user's profile.
- `courses`: the current student's enrolled courses, or the public catalogue when there are no enrolments. Each entry includes an image, a course URL, an `enrolled` flag and `progress` (integer percentage 0-100, or `null` when not applicable).
- `publiccourses`: the public course catalogue, independent of the visitor's enrolments. The administrator's selected public course list is respected.
- `cohorts`: IDs, names and idnumbers for the authenticated user's own cohorts, or an empty array for visitors.
- `popular`: up to six accessible courses ranked by the number of `course_viewed` events in the standard Moodle log over the previous 30 days, including aggregate `views` per course. If standard logs are unavailable or contain no matching events, the array is empty.

The `courses` and `publiccourses` objects include `total`, `limit`, `offset`, `enrolledlist` and `items`. Each request supports `limit=1..48` and a zero-based `offset`; default values are 12 and 0. The server never accepts a user ID parameter.

## Configuration

Go to **Site administration > Appearance > Themes > LearnLingo > Home** and use **Courses shown to visitors** to select which public courses appear in the native LearnLingo home and the `publiccourses` API result.

With no courses selected, all available, visible courses are returned in the order configured in Moodle (`sortorder` then ID). Hidden courses and courses in hidden categories are not public. Logged-in learners retain their personal course list on the native homepage, with the first six courses displayed there as before. The native Moodle home and the separate custom HTML mode remain unchanged.

## Cache and access control

Moodle's core course-category cache already speeds up catalogue lookups. This theme adds a dedicated shared Moodle Universal Cache (MUC) entry named `theme_learnlingo/popularcourses`, with a 15-minute TTL for aggregate course-view ranking only. Visibility is checked again for every request.

Profiles, enrolments, cohorts and completion percentages are **never stored in a shared cache**. Every HTTP response carries `Cache-Control: private, no-store` and `Vary: Cookie`, because even a request for public courses can occur within an authenticated Moodle session. There is no cross-origin API or unauthenticated access to personal data.

Render arbitrary values from the JSON response as **text** in frontend code, not as unescaped HTML.
