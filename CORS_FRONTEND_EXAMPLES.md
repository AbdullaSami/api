# Laravel CORS Configuration - Frontend Examples

This document provides examples for making cross-origin requests to your Laravel API with secure CORS configuration.

## Configuration Summary

- **Frontend Domain**: `https://frontend.example.com`
- **API Domain**: `https://api.example.com`
- **Authentication**: Bearer tokens OR cookies with credentials
- **CORS**: Configured for secure cross-origin requests

## Frontend Request Examples

### 1. Axios Examples

#### With Bearer Token
```javascript
import axios from 'axios';

const token = 'your-sanctum-token-here';

// GET request with Bearer token
axios.get('https://api.example.com/api/user', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
.then(response => {
  console.log('User data:', response.data);
})
.catch(error => {
  console.error('Error:', error.response?.data || error.message);
});

// POST request with Bearer token
axios.post('https://api.example.com/api/v1/user/profile/update', {
  name: 'John Doe',
  email: 'john@example.com'
}, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});
```

#### With Cookies (Credentials)
```javascript
import axios from 'axios';

// Configure axios defaults for credentials
axios.defaults.withCredentials = true;

// GET request with cookies
axios.get('https://api.example.com/api/user', {
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
.then(response => {
  console.log('User data:', response.data);
})
.catch(error => {
  console.error('Error:', error.response?.data || error.message);
});

// POST request with cookies
axios.post('https://api.example.com/api/v1/user/profile/update', {
  name: 'John Doe',
  email: 'john@example.com'
}, {
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});
```

### 2. Fetch API Examples

#### With Bearer Token
```javascript
const token = 'your-sanctum-token-here';

// GET request with Bearer token
fetch('https://api.example.com/api/user', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
.then(response => {
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  return response.json();
})
.then(data => {
  console.log('User data:', data);
})
.catch(error => {
  console.error('Error:', error);
});

// POST request with Bearer token
fetch('https://api.example.com/api/v1/user/profile/update', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john@example.com'
  })
})
.then(response => response.json())
.then(data => console.log('Response:', data))
.catch(error => console.error('Error:', error));
```

#### With Cookies (Credentials)
```javascript
// GET request with credentials
fetch('https://api.example.com/api/user', {
  method: 'GET',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
.then(response => {
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  return response.json();
})
.then(data => {
  console.log('User data:', data);
})
.catch(error => {
  console.error('Error:', error);
});

// POST request with credentials
fetch('https://api.example.com/api/v1/user/profile/update', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john@example.com'
  })
})
.then(response => response.json())
.then(data => console.log('Response:', data))
.catch(error => console.error('Error:', error));
```

### 3. React Hook Example

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';

const useApi = (token) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchUser = async () => {
      try {
        setLoading(true);
        const response = await axios.get('https://api.example.com/api/user', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });
        setUser(response.data);
      } catch (err) {
        setError(err.response?.data?.message || err.message);
      } finally {
        setLoading(false);
      }
    };

    if (token) {
      fetchUser();
    }
  }, [token]);

  return { user, loading, error };
};

// Usage in component
const UserProfile = ({ token }) => {
  const { user, loading, error } = useApi(token);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!user) return <div>No user data</div>;

  return (
    <div>
      <h1>Welcome, {user.user.name}!</h1>
      <p>Email: {user.user.email}</p>
      <p>Auth Method: {user.auth_method}</p>
      <p>Timestamp: {user.timestamp}</p>
    </div>
  );
};
```

## Testing CORS Configuration

### 1. Test Endpoint
Use the `/api/user` endpoint to test CORS configuration:

```bash
# Test with curl (for development)
curl -X GET "https://api.example.com/api/user" \
  -H "Origin: https://frontend.example.com" \
  -H "Authorization: Bearer your-token-here" \
  -v
```

### 2. Browser Console Testing
Open browser console on your frontend and run:

```javascript
// Test CORS with fetch
fetch('https://api.example.com/api/user', {
  method: 'GET',
  credentials: 'include',
  headers: {
    'Authorization': 'Bearer your-token-here',
    'Content-Type': 'application/json'
  }
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
```

## Security Notes

### SameSite=None + Secure Configuration
- **SameSite=none**: Allows cookies to be sent with cross-origin requests
- **Secure=true**: Ensures cookies are only sent over HTTPS
- **Required**: Both must be set together for cross-origin cookies to work

### CORS Headers Explained
- **Access-Control-Allow-Origin**: `https://frontend.example.com` (specific domain, not *)
- **Access-Control-Allow-Credentials**: `true` (allows cookies/credentials)
- **Access-Control-Allow-Methods**: `*` (all HTTP methods)
- **Access-Control-Allow-Headers**: `*` (all headers)

### Why Not Use Wildcard Origins?
When `supports_credentials` is `true`, browsers require a specific origin instead of `*` for security reasons. This prevents malicious sites from making authenticated requests to your API.

## Environment Variables for Production

Add these to your `.env` file for production:

```env
# Session Configuration for CORS
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
SESSION_DOMAIN=.example.com

# App URL (important for CORS)
APP_URL=https://api.example.com

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=https://frontend.example.com
```

## Troubleshooting

### Common CORS Errors

1. **"CORS policy: No 'Access-Control-Allow-Origin' header"**
   - Check that `allowed_origins` includes your frontend domain
   - Verify HandleCors middleware is registered

2. **"CORS policy: Credentials is not supported if wildcard origin"**
   - Ensure `supports_credentials` is `false` when using `*` in `allowed_origins`
   - Or use specific domain with `supports_credentials: true`

3. **"Cookie blocked due to SameSite policy"**
   - Ensure `same_site` is set to `none` and `secure` is `true`
   - Verify your site is served over HTTPS

4. **"Preflight request failed"**
   - Check that `allowed_methods` includes the HTTP method you're using
   - Verify `allowed_headers` includes your custom headers

### Debugging Tips

1. Check browser Network tab for CORS headers
2. Use browser console for detailed error messages
3. Verify `.env` variables are correctly set
4. Test with curl to isolate frontend issues
