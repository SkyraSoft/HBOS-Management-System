import axios from 'axios';
import FormData from 'form-data';
import fs from 'fs';

const api = axios.create({
    baseURL: 'http://localhost:8000/api/v1',
    headers: {
        'Accept': 'application/json'
    }
});

async function testUpload() {
  const form = new FormData();
  form.append('name', 'Test Uploaded Product');
  form.append('cost_price', 100);
  form.append('selling_price', 150);
  form.append('stock', 10);
  form.append('is_active', 1);
  
  // Create a dummy image
  fs.writeFileSync('dummy.png', 'fake image content');
  form.append('image', fs.createReadStream('dummy.png'));

  try {
    const res = await api.post('/products', form, {
      headers: {
        ...form.getHeaders()
      }
    });
    console.log("SUCCESS:", res.status, res.data);
  } catch (err) {
    if (err.response) {
      console.error("API ERROR:", err.response.status, err.response.data);
    } else {
      console.error("AXIOS ERROR:", err.message);
    }
  }
}

testUpload();
