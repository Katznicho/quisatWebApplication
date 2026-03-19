# Parent Assignments Integration (React Native)

This repo now includes:

- `mobile/api/assignments.js`
- `mobile/screens/ParentAssignmentsScreen.js`

Use these snippets in your React Native app folder (the separate app project) to wire navigation and menu links.

## 1) Register Screen in Navigator

```javascript
import { ParentAssignmentsScreen } from './screens/ParentAssignmentsScreen';

// Example stack registration
<Stack.Screen
  name="ParentAssignments"
  options={{ title: 'Assignments' }}
>
  {(props) => (
    <ParentAssignmentsScreen
      {...props}
      baseUrl={API_BASE_URL} // e.g. https://yourdomain.com/api/v1
      token={authToken}
    />
  )}
</Stack.Screen>
```

## 2) Add Menu / Button Entry

```javascript
navigation.navigate('ParentAssignments');
```

## 3) Required Backend Endpoints (already implemented)

- `GET /api/v1/assignments`
- `GET /api/v1/assignments/hidden-for-parent`
- `DELETE /api/v1/assignments/{id|uuid}` (parent = hide only)
- `POST /api/v1/assignments/{id|uuid}/restore-for-parent`

## 4) Behavior Notes

- Parent delete removes assignment from that parent only.
- Staff delete still deletes assignment globally.
- Hidden assignments are excluded from parent dashboard and parent list.
- Parent can restore hidden assignments from the Hidden section.

## 5) Quick Smoke Test

1. Parent opens Assignments screen and sees active assignments.
2. Parent taps `Remove from my view`.
3. Item disappears from Active and appears in Hidden.
4. Parent taps `Restore`.
5. Item returns to Active.
6. Staff delete on same assignment removes it for everyone.

